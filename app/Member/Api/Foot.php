<?php

namespace App\Member\Api;

use App\Member\Event\CollectEvent;
use App\Member\Models\MemberCollect;
use App\Member\Models\MemberFoot;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Params;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Handlers\ExceptionNotFound;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpUnauthorizedException;

#[RouteGroup(app: 'apiMember', route: '/member/foot')]
#[Docs(name: '足迹管理')]
class Foot
{
    #[Route(methods: 'GET', route: '/{type}')]
    #[Api(name: '足迹列表')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '足迹类型', desc: '足迹的内容类型')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '足迹列表', desc: '用户足迹列表', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
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

        $query = MemberFoot::query();
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

    #[Route(methods: 'POST', route: '/{type}/{id}')]
    #[Api(name: '记录足迹')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '足迹类型', desc: '足迹的内容类型')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '内容ID', desc: '要记录足迹的内容ID')]
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
            \App\Member\Service\Foot::run(
                userId: $userId,
                hasType: $type['class'],
                hasId: (int)$args['id'],
            );
            App::db()->getConnection()->commit();
        } catch (\Exception $e) {
            App::db()->getConnection()->rollBack();
            throw $e;
        }
        return send($response, "ok");
    }

    #[Route(methods: 'DELETE', route: '/{type}')]
    #[Api(name: '删除足迹')]
    #[Params(field: 'type', type: FieldEnum::STRING, name: '足迹类型', desc: '足迹的内容类型')]
    #[Payload(field: 'ids', type: FieldEnum::ARRAY, name: '内容ID列表', desc: '要删除的内容ID数组')]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody() ?: [];
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        $event = new CollectEvent();
        App::event()->dispatch($event, 'member.collect');
        $type = $event->getMapType($args['type']);
        if (!$type) {
            throw new ExceptionNotFound();
        }

        $ids = $data['ids'];
        if (!$ids) {
            throw new ExceptionBusiness('请选择删除项');
        }
        $ids = is_array($ids) ? $ids : [$ids];
        MemberFoot::query()->where('user_id', $userId)->where('has_type', $type['class'])->whereIn('has_id', $ids)->delete();

        return send($response, "删除成功");
    }
}