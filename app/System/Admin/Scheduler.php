<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Models\SystemSchedulerTask;
use App\System\Service\SchedulerService;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cron\CronExpression;

#[Resource(app: 'admin', route: '/system/scheduler', name: 'system.scheduler')]
class Scheduler extends Resources
{
    protected string $model = SystemSchedulerTask::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams() + [
            'keyword' => null,
            'status' => null,
        ];

        if ($params['keyword']) {
            $keyword = (string)$params['keyword'];
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('name', 'like', "%{$keyword}%")
                    ->orWhere('selected_task', 'like', "%{$keyword}%")
                    ->orWhere('desc', 'like', "%{$keyword}%");
            });
        }

        if ($params['status']) {
            $status = (int)$params['status'] === 1 ? 1 : 0;
            $query->where('status', $status);
        }

        $query->orderByDesc('sort')->orderByDesc('id');
    }

    public function transform(object $item): array
    {
        /** @var SystemSchedulerTask $item */
        return $item->transform();
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        $options = SchedulerService::getAttributeOptions();
        $taskValues = collect($options)->pluck('value')->filter()->values()->toArray();

        \Valitron\Validator::addRule('cron', static function ($field, $value, array $params, array $fields): bool {
            return $value !== '' && CronExpression::isValidExpression((string)$value);
        });

        return [
            'selected_task' => [
                ['required', '请选择任务'],
                ['in', $taskValues, '任务不存在'],
            ],
            'name' => [
                ['required', '请输入任务名称'],
            ],
            'cron' => [
                ['required', '请输入 Cron 表达式'],
                ['cron', 'Cron 表达式不正确'],
            ],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        $selectedTask = (string)$data->selected_task;

        $current = null;
        $id = (int)($args['id'] ?? 0);
        if ($id > 0) {
            $current = SystemSchedulerTask::query()->where('id', $id)->first();
        }

        $status = $data->status;
        if ($status === null || $status === '') {
            $status = 0;
        }

        $name = $data->name;
        if ($name === null || $name === '') {
            $name = (string)($current?->name ?? '');
        }
        $desc = $data->desc;
        if ($desc === null || $desc === '') {
            $desc = (string)($current?->desc ?? '');
        }
        if ($selectedTask === '') {
            $selectedTask = (string)($current?->selected_task ?? '');
        }

        $cron = $data->cron;
        if ($cron === null || $cron === '') {
            $cron = $current?->cron ?? '';
        }
        $cron = trim((string)$cron);

        return [
            'selected_task' => (string)$selectedTask,
            'name' => (string)$name,
            'cron' => $cron,
            'desc' => (string)$desc,
            'sort' => (int)($data->sort ?? 0),
            'status' => (int)$status,
        ];
    }

    public function createAfter(Data $data, mixed $info): void
    {
        SchedulerService::generate();
    }

    public function storeAfter(Data $data, mixed $info): void
    {
        SchedulerService::generate();
    }

    public function delAfter(mixed $info): void
    {
        SchedulerService::generate();
    }

    #[Action(methods: 'GET', route: '/options')]
    public function options(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return send($response, 'ok', [
            'tasks' => SchedulerService::getAttributeOptions(),
        ]);
    }
}
