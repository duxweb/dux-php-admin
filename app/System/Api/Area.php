<?php

namespace App\System\Api;

use App\System\Models\SystemArea;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'api', route: '')]
#[Docs(name: '地区')]
class Area
{

    #[Route(methods: 'GET', route: '/system/area')]
    #[Api(name: '地区列表', payloadExample: [
        'level' => 0,
        'parent' => '',
    ])]
    #[Query(field: 'level', type: FieldEnum::INT, name: '地区等级', desc: '地区等级')]
    #[Query(field: 'parent', type: FieldEnum::STRING, name: '父级地区名', desc: '父级地区名')]

    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '地区列表', desc: '地区列表', root: true, children: [
        new ResultData(field: 'name', type: FieldEnum::STRING, name: '地区名称', desc: '地区名称'),
        new ResultData(field: 'code', type: FieldEnum::STRING, name: '地区编码', desc: '地区编码'),
        new ResultData(field: 'parent_code', type: FieldEnum::STRING, name: '父级编码', desc: '父级编码'),
        new ResultData(field: 'level', type: FieldEnum::INT, name: '地区等级', desc: '地区等级'),
        new ResultData(field: 'leaf', type: FieldEnum::BOOL, name: '是否叶子节点', desc: '是否叶子节点'),
    ])]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        $level = $params['level'] ?: 0;
        $parent = $params['parent'];
        $model = new SystemArea();
        $info = $model->query()->where('name', $parent)->where('level', $level)->first();
        $data = $model->query()->where('level', $level + 1)->where('parent_code', $parent ? $info['code'] : 0)->get(["name as value", "name as label", "leaf"])->toArray();
        return send($response, 'ok', $data);
    }
}
