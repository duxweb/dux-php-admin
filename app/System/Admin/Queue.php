<?php

declare(strict_types=1);

namespace App\System\Admin;

use Core\App;
use App\System\Models\SystemQueueLog;
use App\System\Queue\PingJob;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Handlers\ExceptionBusiness;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'admin', route: '/system/queue', name: 'system.queue')]
class Queue
{
    #[Route(methods: 'GET', route: '')]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $rows = $this->mergeRuntimeStats(App::queue()->stats());
        $data = format_data(collect($rows), static fn (array $row) => $row);

        return send($response, 'ok', $data['data'], $data['meta']);
    }

    #[Route(methods: 'GET', route: '/log')]
    public function log(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams() + [
            'work' => null,
            'event' => null,
            'keyword' => null,
        ];

        $query = SystemQueueLog::query();
        if ($params['work']) {
            $query->where('work', (string)$params['work']);
        }
        if ($params['event']) {
            $query->where('event', (string)$params['event']);
        }
        if ($params['keyword']) {
            $keyword = (string)$params['keyword'];
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('job_class', 'like', "%{$keyword}%")
                    ->orWhere('job_method', 'like', "%{$keyword}%")
                    ->orWhere('error_message', 'like', "%{$keyword}%");
            });
        }

        $query->orderByDesc('id');
        $data = format_data($query->paginate(20), static fn (SystemQueueLog $log) => $log->transform());

        return send($response, 'ok', $data['data'], $data['meta']);
    }

    #[Route(methods: 'POST', route: '/test')]
    public function test(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = (array)$request->getParsedBody() + [
            'work' => null,
        ];

        $work = (string)$params['work'];

        App::queue()->add(PingJob::class, '', [$work], $work)->send();

        return send($response, 'ok', [
            'work' => $work,
        ]);
    }

    private function mergeRuntimeStats(array $rows): array
    {
        $metrics = $this->runtimeMetrics();
        if (!$metrics) {
            return $rows;
        }

        $dispatchers = [];
        foreach ($metrics['queue']['dispatchers'] ?? [] as $item) {
            $name = (string)($item['name'] ?? '');
            if (!$name) {
                continue;
            }
            $dispatchers[$name] = $item;
        }

        foreach ($rows as &$row) {
            $name = (string)($row['name'] ?? '');
            $runtime = $dispatchers[$name] ?? null;
            if (!$runtime) {
                continue;
            }
            $row['running'] = $runtime['active'] ?? $row['running'];
        }
        unset($row);

        return $rows;
    }

    private function runtimeMetrics(): ?array
    {
        $runtimePackage = base_path('vendor/duxweb/dux-runtime');
        if (!is_dir($runtimePackage)) {
            return null;
        }

        $port = (string)App::config('use')->get('runtime.realtime_addr', getenv('DUX_RUNTIME_REALTIME_ADDR') ?: ':9504');
        $url = str_starts_with($port, ':') ? 'http://127.0.0.1' . $port : 'http://' . $port;
        $payload = @file_get_contents(rtrim($url, '/') . '/metrics');
        if (!$payload) {
            return null;
        }

        $data = json_decode($payload, true);
        if (!is_array($data) || !is_array($data['runtime'] ?? null)) {
            return null;
        }

        return $data['runtime'];
    }
}
