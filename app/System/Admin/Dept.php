<?php

namespace App\System\Admin;

use App\System\Models\SystemDept;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/dept', name: 'system.dept')]
class Dept extends Resources
{

    protected string $model =  SystemDept::class;

    protected bool $tree = true;
    protected array $pagination = [
        'status' => false,
    ];

    public array $excludesMany = ['permission'];

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "parent_id" => $item->parent_id,
            "name" => $item->name,
            "children" => $item->children ? $item->children->map(function ($vo) {
                return $this->transform($vo);
            }) : []
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => ["required", '请输入部门名称'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => $data->name,
            "parent_id" => $data->parent_id ?: 0,
        ];
    }
}
