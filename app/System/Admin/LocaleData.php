<?php

namespace App\System\Admin;

use App\System\Models\SystemLocaleData;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/localeData', name: 'system.localeData')]
class LocaleData extends Resources
{

    protected string $model =  SystemLocaleData::class;


    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();
        if ($params['keyword']) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "name" => $item->name,
            "data" => $item->data,
            "time" => $item->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => ["required", '请输入语言名'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => $data->name,
            "data" => $data->data,
        ];
    }
}
