<?php

namespace App\Member\Api;

use App\Member\Event\PraiseEvent;
use App\Member\Models\MemberPraise;
use Core\App;
use Core\Auth\Auth;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Params;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Member\Interface\PraiseInterface;

#[RouteGroup(app: 'api', route: '/member/praise')]
#[Docs(name: '点赞管理')]
class Praise
{

    #[Route(methods: 'GET', route: '/{type}/{id}')]
    #[Api(name: '点赞状态')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '点赞类型', desc: '点赞的内容类型')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '内容ID', desc: '要查询的内容ID')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '点赞状态', desc: '点赞状态信息', children: [
        new ResultData(field: 'status', type: FieldEnum::BOOL, name: '点赞状态', desc: '是否已点赞'),
        new ResultData(field: 'count', type: FieldEnum::INT, name: '点赞数量', desc: '总点赞数量')
    ], root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $data = $request->getParsedBody();
        $auth = (new Auth())->decode($request, 'member');
        $userId = (int)$auth['id'];

        $event = new PraiseEvent();
        App::event()->dispatch($event, 'member.praise');
        $type = $event->getMapType($args['type']);


        $info = MemberPraise::query()->where('user_id', $userId)->where('has_type', $type['class'])->where('has_id', $args['id'])->first();
        $count = MemberPraise::query()->where('has_type', $type['class'])->where('has_id', $args['id'])->count();

        return send($response, "ok", [
            'status' => (bool)$info,
            'count' => $count
        ]);
    }

    #[Route(methods: 'POST', route: '/{type}/{id}')]
    #[Api(name: '点赞/取消点赞')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '点赞类型', desc: '点赞的内容类型')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '内容ID', desc: '要点赞的内容ID')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '点赞状态', desc: '点赞操作结果', children: [
        new ResultData(field: 'status', type: FieldEnum::BOOL, name: '点赞状态', desc: '操作后的点赞状态')
    ], root: true)]
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = (new Auth())->decode($request, 'member');
        $userId = (int)$auth['id'];

        $event = new PraiseEvent();
        App::event()->dispatch($event, 'member.praise');
        $type = $event->getMapType($args['type']);

        /**
         * @var PraiseInterface $praise
         */
        $praise = $type['praise'];

        App::db()->getConnection()->beginTransaction();
        try {
            $status = \App\Member\Service\Praise::run($userId, $type['class'], (int)$args['id'], $praise);
            App::db()->getConnection()->commit();
        } catch (\Exception $e) {
            App::db()->getConnection()->rollBack();
            throw $e;
        }

        return send($response, "ok", [
            'status' => $status
        ]);
    }
}