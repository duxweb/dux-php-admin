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
        $rows = App::queue()->stats();
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
}
