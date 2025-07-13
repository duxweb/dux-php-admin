<?php

namespace App\Member\Api;

use App\Member\Event\CollectEvent;
use App\Member\Models\MemberCollect;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Params;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionNotFound;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpUnauthorizedException;
use App\Member\Interface\CollectInterface;

#[RouteGroup(app: 'apiMember', route: '/member/collect')]
#[Docs(name: '收藏管理')]
class Collect
{
    #[Route(methods: 'GET', route: '/{type}')]
    #[Api(name: '收藏列表')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '收藏类型', desc: '收藏的内容类型')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '收藏列表', desc: '用户收藏列表', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        $event = new CollectEvent();
        App::event()->dispatch($event, 'member.collect');
        $type = $event->getMapType($args['type']);
        if (!$type) {
            throw new ExceptionNotFound();
        }

        /**
         * @var CollectInterface $collect
         */
        $collect = $type['collect'];

        $query = MemberCollect::query();
        $query->where('has_type', $type['class'])->where('user_id', $userId);
        $query->orderByDesc('id');
        $list  = $query->paginate(20);
        $data = format_data($list, function ($item) use ($collect) {
            $newItem = [
                'id' => $item->has_id,
                'created_at' => $item->created_at->format('Y-m-d H:i:s')
            ];
            if ($collect) {
                $newItem = [...$newItem, ...$collect->format($item->hastable)];
            } else {
                $newItem = [...$newItem, [
                    'title' => $item->hastable?->title,
                    'image' => $item->hastable?->image ?: $item->hastable?->images[0] ?: '',
                ]];
            }
            return $newItem;
        });

        return send($response, "ok", ...$data);
    }

    #[Route(methods: 'GET', route: '/{type}/{id}')]
    #[Api(name: '收藏状态')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '收藏类型', desc: '收藏的内容类型')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '内容ID', desc: '要查询的内容ID')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '收藏状态', desc: '收藏状态信息', children: [
        new ResultData(field: 'status', type: FieldEnum::BOOL, name: '收藏状态', desc: '是否已收藏')
    ], root: true)]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        $event = new CollectEvent();
        App::event()->dispatch($event, 'member.collect');
        $type = $event->getMapType($args['type']);
        if (!$type) {
            throw new ExceptionNotFound();
        }

        $count = \App\Member\Service\Collect::count($userId, $type['class'], (int)$args['id']);
        return send($response, "ok", [
            'status' => (boolean)$count
        ]);
    }

    #[Route(methods: 'POST', route: '/{type}/{id}')]
    #[Api(name: '收藏/取消收藏')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '收藏类型', desc: '收藏的内容类型')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '内容ID', desc: '要收藏的内容ID')]
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        if (!$userId) {
            throw new HttpUnauthorizedException($request);
        }
        $event = new CollectEvent();
        App::event()->dispatch($event, 'member.collect');
        $type = $event->getMapType($args['type']);
        if (!$type) {
            throw new ExceptionNotFound();
        }

        App::db()->getConnection()->beginTransaction();
        try {
            \App\Member\Service\Collect::run($userId, $type['class'], (int)$args['id'], $type['collect']);
            App::db()->getConnection()->commit();
        } catch (\Exception $e) {
            App::db()->getConnection()->rollBack();
            throw $e;
        }
        return send($response, "ok");
    }
}