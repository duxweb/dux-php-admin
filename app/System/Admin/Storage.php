<?php

namespace App\System\Admin;

use App\System\Models\SystemStorage;
use Carbon\Carbon;
use Core\Handlers\ExceptionBusiness;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/storage', name: 'system.storage')]
class Storage extends Resources
{

    protected string $model =  SystemStorage::class;

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
            "name" => $item->name,
            "type" => $item->type,
            "type_name" => $item->type_name,
            "config" => $item->config,
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        if ($data['type'] == 'json') {
            if (!json_validate($data['value'])) {
                throw new ExceptionBusiness('JSON格式错误');
            }
        }

        return [
            "title" => ["required", '请输入标题'],
            "name" => ["required", '请输入名称'],
            "type" => ["required", '请选择类型'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => $data->title,
            "name" => $data->name,
            "type" => $data->type,
            "config" => $data->config,
        ];
    }
}
