<?php

namespace App\Member\Api;

use App\System\Models\SystemArea;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Docs(name: '地区管理')]
class Area
{

    #[Route(methods: 'GET', route: '/member/area', app: 'apiMember')]
    #[Api(name: '地区列表', payloadExample: ['level' => 0, 'parent' => '广东省'])]
    #[Query(field: 'level', type: FieldEnum::INT, name: '地区等级', required: false, desc: '地区等级：0-省份，1-城市，2-区县')]
    #[Query(field: 'parent', type: FieldEnum::STRING, name: '上级地区', required: false, desc: '上级地区名称')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '地区列表', desc: '地区数据列表', children: [
        new ResultData(field: 'value', type: FieldEnum::STRING, name: '地区名称', desc: '地区名称'),
        new ResultData(field: 'label', type: FieldEnum::STRING, name: '地区标签', desc: '地区显示标签'),
        new ResultData(field: 'leaf', type: FieldEnum::BOOL, name: '是否叶子节点', desc: '是否为最后一级地区')
    ], root: true)]
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