<?php

namespace App\Member\Api;

use App\Member\Event\AssessEvent;
use App\Member\Models\MemberAssess;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Params;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'api', route: '/member/assess')]
#[Docs(name: '评价管理')]
class Assess
{
    #[Route(methods: 'GET', route: '/has')]
    #[Api(name: '评价列表', payloadExample: ['type' => 'article', 'id' => 1, 'score' => 3])]
    #[Query(field: 'type', type: FieldEnum::STRING, name: '评价类型', desc: '评价的内容类型')]
    #[Query(field: 'id', type: FieldEnum::INT, name: '内容ID', desc: '被评价内容的ID')]
    #[Query(field: 'score', type: FieldEnum::INT, name: '评分范围', required: false, desc: '评分范围：1-差评，2-中评，3-好评')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '评价列表', desc: '评价数据列表', root: true)]
    public function has(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        $type = $params['type'];
        $id = $params['id'];
        $range = $params['score'];

        $assess = new AssessEvent();
        App::event()->dispatch($assess, 'member.assess');
        $typeData = $assess->getType($type);
        if (!$typeData) {
            throw new ExceptionBusiness('类型不存在');
        }
        if (!$id) {
            throw new ExceptionBusiness('id不存在');
        }

        $query = MemberAssess::query()->where('has_type', $typeData['class'])->where('has_id', $id)->where('status', 1);

        switch ($range) {
            case 1:
                $query->whereBetween('score', [1, 1]);
                break;
            case 2:
                $query->whereBetween('score', [2, 3]);
                break;
            case 3:
                $query->whereBetween('score', [4, 5]);
                break;
        }

        $query->orderByDesc('id');
        $list  = $query->paginate(20);
        $data = format_data($list, function ($item) use ($typeData) {
            return \App\Member\Service\Assess::formatData($item, $typeData['assess']);
        });

        return send($response, 'ok', ...$data);
    }

    #[Route(methods: 'GET', route: '/total')]
    #[Api(name: '评价统计', payloadExample: ['type' => 'article', 'id' => 1])]
    #[Query(field: 'type', type: FieldEnum::STRING, name: '评价类型', desc: '评价的内容类型')]
    #[Query(field: 'id', type: FieldEnum::INT, name: '内容ID', desc: '被评价内容的ID')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '评价统计', desc: '评价统计数据', children: [
        new ResultData(field: 'avg', type: FieldEnum::FLOAT, name: '平均评分', desc: '平均评分'),
        new ResultData(field: 'negative', type: FieldEnum::INT, name: '差评数', desc: '差评数量'),
        new ResultData(field: 'medium', type: FieldEnum::INT, name: '中评数', desc: '中评数量'),
        new ResultData(field: 'good', type: FieldEnum::INT, name: '好评数', desc: '好评数量')
    ], root: true)]
    public function total(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        $type = $params['type'];
        $id = $params['id'];

        $assess = new AssessEvent();
        App::event()->dispatch($assess, 'member.assess');
        $typeData = $assess->getType($type);
        if (!$typeData) {
            throw new ExceptionBusiness('类型不存在');
        }
        if (!$id) {
            throw new ExceptionBusiness('id不存在');
        }

        $negative = MemberAssess::query()->where('has_type', $typeData['class'])->where('has_id', $id)->whereBetween('score', [1, 1])->count();
        $medium = MemberAssess::query()->where('has_type', $typeData['class'])->where('has_id', $id)->whereBetween('score', [2, 3])->count();
        $good = MemberAssess::query()->where('has_type', $typeData['class'])->where('has_id', $id)->whereBetween('score', [4, 5])->count();


        return send($response, 'ok', [
            'avg' => \App\Member\Service\Assess::calculateScore($typeData['class'], $id),
            'negative' => $negative,
            'medium' => $medium,
            'good' => $good,
        ]);
    }

    #[Route(methods: 'GET', route: '/source')]
    #[Api(name: '评价来源', payloadExample: ['type' => 'user', 'id' => '1,2,3'])]
    #[Query(field: 'type', type: FieldEnum::STRING, name: '来源类型', desc: '评价来源类型')]
    #[Query(field: 'id', type: FieldEnum::STRING, name: '来源ID', desc: '评价来源ID，多个用逗号分隔')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '评价来源', desc: '评价来源数据列表', root: true)]
    public function source(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        $type = $params['type'];
        $ids = explode(',', $params['id']);

        $assess = new AssessEvent();
        App::event()->dispatch($assess, 'member.assess');
        $sourceData = $assess->getSource($type);
        if (!$sourceData) {
            throw new ExceptionBusiness('类型不存在');
        }
        if (!$ids) {
            throw new ExceptionBusiness('id不存在');
        }

        $list = MemberAssess::query()->where('source_type', $sourceData['class'])->whereIn('source_id', $ids)->paginate(20);
        $data = format_data($list, function ($item) use ($sourceData) {
            return \App\Member\Service\Assess::formatData($item, $sourceData['assess']);
        });
        return send($response, 'ok', $data);
    }

}