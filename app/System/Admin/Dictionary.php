<?php

namespace App\System\Admin;

use App\System\Models\SystemDictionary;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/dictionary', name: 'system.dictionary')]
class Dictionary extends Resources
{

    protected string $model =  SystemDictionary::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args)
    {
        $params = $request->getQueryParams();
        if ($params['keyword']) {
            $query->where('title', 'like', '%' . $params['keyword'] . '%');
        }
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "title" => $item->title,
            "key" => $item->key,
            "value" => $item->value,
            "remark" => $item->remark,
            "type" => $item->type,
            "type_name" => $item->type_name,
            "time" => $item->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => ["required", '请输入名称'],
            "key" => ["required", '请输入键名'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => $data->title,
            "key" => $data->key,
            "remark" => $data->remark,
            "type" => $data->type,
        ];
    }
}
