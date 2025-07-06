<?php

namespace App\System\Admin;

use App\System\Models\SystemDictionaryData;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Resource;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin',  route: '/system/dictionaryData/{did}', name: 'system.dictionaryData')]
class DictionaryData extends Resources
{

    protected string $model =  SystemDictionaryData::class;

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args)
    {
        $params = $request->getQueryParams();

        $query->where('dictionary_id', $args['did']);

        if ($params['keyword']) {
            $query->where('title', 'like', '%' . $params['keyword'] . '%');
        }
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "title" => $item->title,
            "value" => $item->format_value,
            "value_show" => $item->value,
            "remark" => $item->remark,
            "time" => $item->created_at,
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => ["required", '请输入名称'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "title" => $data->title,
            "value" => $data->value,
            "remark" => $data->remark,
            "dictionary_id" => $data->dictionary_id,
        ];
    }
}
