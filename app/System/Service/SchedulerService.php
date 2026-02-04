<?php

declare(strict_types=1);

namespace App\System\Service;

use App\System\Models\SystemSchedulerTask;
use Core\App;

class SchedulerService
{
    public static function buildJobs(): array
    {
        $tasks = SystemSchedulerTask::query()
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->get();

        $options = collect(self::getAttributeOptions())->keyBy('value');

        return $tasks->map(static function (SystemSchedulerTask $task) use ($options): ?array {
            $selectedTask = (string)$task->selected_task;
            $option = $options->get($selectedTask);
            if (!$option) {
                return null;
            }

            return [
                'name' => (string)$task->name,
                'desc' => (string)$task->desc,
                'cron' => trim((string)$task->cron),
                'callback' => (string)$option['callback'],
            ];
        })->filter()->values()->toArray();
    }

    public static function generate(): void
    {
        App::scheduler()->gen();
    }

    /**
     * @return array<int, array{label:string,value:string,name:string,callback:string,cron:string,desc:string}>
     */
    public static function getAttributeOptions(): array
    {
        $scheduler = new \Core\Scheduler\Scheduler();
        $scheduler->registerAttribute();
        $data = $scheduler->getData();

        return collect($data)
            ->filter(static fn (array $item): bool => (string)($item['name'] ?? '') !== '')
            ->map(static function (array $item): array {
                $name = (string)($item['name'] ?? '');
                $callback = (string)($item['callback'] ?? '');
                $desc = (string)($item['desc'] ?? '');
                $label = $desc !== '' ? "{$name} - {$desc}" : $name;

                return [
                    'label' => $label,
                    'value' => $name,
                    'name' => $name,
                    'callback' => $callback,
                    'cron' => '',
                    'desc' => $desc,
                ];
            })->values()->toArray();
    }
}
