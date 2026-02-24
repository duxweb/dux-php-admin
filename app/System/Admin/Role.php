<?php

namespace App\System\Admin;

use App\System\Models\SystemMenu;
use App\System\Models\SystemRole;
use Core\App;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/role', name: 'system.role')]
class Role extends Resources
{

    protected string $model =  SystemRole::class;


    public array $excludesMany = ['permission'];

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "name" => $item->name,
            "desc" => $item->desc,
            "data_type" => $item->data_type,
            "data_permission" => $item->data_permission,
            "permission" => array_filter($item->permission ?: [])
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => ["required", '请输入角色名称'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => $data->name,
            "desc" => $data->desc,
            "data_type" => $data->data_type ?: 0,
            "data_permission" => $data->data_permission,
            "permission" => $data->permission ?: [],
        ];
    }

    #[Action(methods: 'GET', route: '/permission')]
    public function permission(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $list = SystemMenu::query()
            ->withDepth()
            ->defaultOrder()
            ->where('app', 'admin')
            ->where('hidden', 0)
            ->where('type', 'menu')
            ->get();

        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'label' => $item->label,
                'name' => $item->name,
                'children' => $item->buttons ?: []
            ];
        }

        return send($response, 'ok', $data);
    }
}
