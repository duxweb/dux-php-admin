<?php

namespace App\Member\Api;

use App\Member\Event\CollectEvent;
use App\Member\Event\PraiseEvent;
use App\Member\Models\MemberNoticeCollect;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/noticeCollect')]
#[Docs(name: '收藏通知')]
class NoticeCollect
{

    #[Route(methods: 'GET', route: '')]
    #[Api(name: '收藏通知列表')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '收藏通知列表', desc: '用户收藏通知列表', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');

        $list = MemberNoticeCollect::query()->where('user_id', $auth['id'])->orderByDesc('id')->paginate(10);

        $event = new CollectEvent();
        App::event()->dispatch($event, 'member.collect');


        $praiseEvent = new PraiseEvent();
        App::event()->dispatch($praiseEvent, 'member.praise');

        MemberNoticeCollect::query()->where('user_id', $auth['id'])->update(['read' => true]);

        $data = format_data($list, function ($item) use ($event, $praiseEvent) {
            return [
                'id' => $item->id,
                'has_type' => $event->getName($item->has_type) ?: $praiseEvent->getName($item->has_type),
                'has_id' => $item->has_id,
                'from_has_type' => $event->getName($item->from_has_type) ?: $praiseEvent->getName($item->from_has_type),
                'from_has_id' => $item->from_has_id,
                'cover' => $item->cover,
                'content' => $item->content,
                'type' => $item->type,
                'read' => (bool)$item->read,
                'user' => [
                    'id' => $item->fromUser->id,
                    'nickname' => $item->fromUser->nickname,
                    'avatar' => $item->fromUser->avatar
                ],
                'created_at' => $item->created_at
            ];
        });

        return send($response, "ok", ...$data);
    }
}
