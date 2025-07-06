<?php

namespace App\System\Admin;

use App\System\Models\SystemSetting;
use Carbon\Carbon;
use Core\Handlers\ExceptionBusiness;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/setting', name: 'system.setting')]
class Setting extends Resources
{

    protected string $model =  SystemSetting::class;

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
            "value" => $item->format_value,
            "remark" => $item->remark,
            "type" => $item->type,
            "type_name" => $item->type_name,
            "time" => $item->created_at->format('Y-m-d H:i:s'),
            "public" => !!$item->public,
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
            "title" => ["required", '请输入名称'],
            "key" => ["required", '请输入键名'],
            "type" => ["required", '请选择类型'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => $data->title,
            "key" => $data->key,
            "value" => $data->value,
            "remark" => $data->remark,
            "type" => $data->type,
            "public" => !!$data->public,
        ];
    }
}
