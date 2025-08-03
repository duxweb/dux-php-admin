<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Models\SystemBulletin;
use App\System\Models\SystemDept;
use App\System\Models\SystemRole;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/system/bulletin', name: 'system.bulletin')]
class Bulletin extends Resources
{
    protected string $model = SystemBulletin::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();

        switch ($params['tab'] ?? '') {
            case '1': $query->where('status', 1); break;
            case '2': $query->where('status', 0); break;
        }

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['keyword'])) {
            $query->where(function ($q) use ($params) {
                $q->where('title', 'like', '%' . $params['keyword'] . '%')
                  ->orWhere('content', 'like', '%' . $params['keyword'] . '%');
            });
        }

        $query->orderBy('is_top', 'desc')
              ->orderBy('sort', 'desc')
              ->orderBy('created_at', 'desc');
    }

    public function transform(object $item): array
    {
        return $item->transform();
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => ["required", '请输入公告标题'],
            "content" => ["required", '请输入公告内容'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        $auth = $request->getAttribute("auth");
        
        $formatData = [
            'title' => $data->title,
            'content' => $data->content,
            'type' => $data->type ?? 1,
            'target_type' => $data->target_type ?? 1,
            'target_departments' => $data->target_departments ?? null,
            'target_roles' => $data->target_roles ?? null,
            'is_top' => $data->is_top ?? false,
            'sort' => $data->sort ?? 0,
            'publish_at' => $data->publish_at ?? now(),
            'expire_at' => $data->expire_at ?? null,
            'status' => $data->status ?? 1,
        ];

        // 只在创建时设置创建者信息
        if (!isset($args['id'])) {
            $formatData['user_id'] = $auth['id'];
        }

        return $formatData;
    }

    #[Action(methods: 'GET', route: '/options')]
    public function options(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $departments = SystemDept::query()->select(['id', 'name'])->get()->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id,
            ];
        });

        $roles = SystemRole::query()->select(['id', 'name'])->get()->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id,
            ];
        });

        $data = [
            'departments' => $departments,
            'roles' => $roles,
            'types' => [
                ['label' => '通知', 'value' => 1],
                ['label' => '公告', 'value' => 2],
                ['label' => '活动', 'value' => 3],
            ],
            'target_types' => [
                ['label' => '全部用户', 'value' => 1],
                ['label' => '指定部门', 'value' => 2],
                ['label' => '指定角色', 'value' => 3],
            ]
        ];

        return send($response, "ok", $data);
    }
}