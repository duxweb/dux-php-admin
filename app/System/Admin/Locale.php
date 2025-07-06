<?php

namespace App\System\Admin;

use App\System\Models\SystemLocale;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/locale', name: 'system.locale')]
class Locale extends Resources
{

    protected string $model =  SystemLocale::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
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
            "time" => $item->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => ["required", '请输入语言名称'],
            "name" => ["required", '请输入语言名'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => $data->title,
            "name" => $data->name,
        ];
    }
}
