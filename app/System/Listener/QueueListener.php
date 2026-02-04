<?php

declare(strict_types=1);

namespace App\System\Listener;

use App\System\Models\SystemQueueLog;
use Core\Event\Attribute\Listener;
use Core\Queue\QueueEvent;

class QueueListener
{
    private static array $startMap = [];

    #[Listener(name: QueueEvent::ENQUEUE)]
    public function enqueue(QueueEvent $event): void
    {
        $this->record($event, 'enqueue', 0);
    }

    #[Listener(name: QueueEvent::EXECUTE)]
    public function execute(QueueEvent $event): void
    {
        $key = spl_object_id($event->message);
        self::$startMap[$key] = (int)round(microtime(true) * 1000);
        $this->record($event, 'execute', 0);
    }

    #[Listener(name: QueueEvent::DONE)]
    public function done(QueueEvent $event): void
    {
        $duration = $this->resolveDuration($event);
        $this->record($event, 'done', $duration);
    }

    #[Listener(name: QueueEvent::FAILED)]
    public function failed(QueueEvent $event): void
    {
        $duration = $this->resolveDuration($event);
        $this->record($event, 'failed', $duration);
    }

    private function resolveDuration(QueueEvent $event): int
    {
        $key = spl_object_id($event->message);
        $startMs = (int)(self::$startMap[$key] ?? 0);
        if ($startMs <= 0) {
            return 0;
        }
        unset(self::$startMap[$key]);
        return max(0, (int)round(microtime(true) * 1000) - $startMs);
    }

    private function record(QueueEvent $event, string $type, int $duration): void
    {
        $message = $event->message;
        $method = $message->method !== '' ? $message->method : '__invoke';
        $exception = $event->exception;
        $jobId = (string)$message->id;

        if ($jobId !== '') {
            $log = SystemQueueLog::query()
                ->where('job_id', $jobId)
                ->where('work', $event->work)
                ->first();
        } else {
            $log = SystemQueueLog::query()
                ->where('work', $event->work)
                ->where('priority', $event->priority)
                ->where('job_class', $message->class)
                ->where('job_method', $method)
                ->whereIn('event', ['enqueue', 'execute'])
                ->orderByDesc('id')
                ->first();
        }

        if (!$log) {
            $log = new SystemQueueLog();
            $log->job_id = $jobId;
            $log->work = $event->work;
            $log->priority = $event->priority;
            $log->job_class = $message->class;
            $log->job_method = $method;
            $log->params_json = (string)json_encode($message->params, JSON_UNESCAPED_UNICODE);
            $log->delay_ms = (int)$event->delayMs;
        }

        $log->work = $event->work;
        $log->priority = $event->priority;
        $log->event = $type;
        $log->error_message = $exception?->getMessage();
        $log->error_file = $exception ? ($exception->getFile() . ':' . $exception->getLine()) : null;
        $log->duration_ms = (int)$duration;
        $log->save();

        SystemQueueLog::query()
            ->where('created_at', '<', now()->subDays(3))
            ->delete();
    }
}
