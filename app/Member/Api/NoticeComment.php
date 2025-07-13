<?php

namespace App\Member\Api;

use App\Member\Event\CommentEvent;
use App\Member\Models\MemberNoticeComment;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/noticeComment')]
#[Docs(name: '评论通知')]
class NoticeComment
{

    #[Route(methods: 'GET', route: '')]
    #[Api(name: '评论通知列表')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '评论通知列表', desc: '用户评论通知列表', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');

        $list = MemberNoticeComment::query()->with('comment', 'comment.user', 'comment.praises')->where('user_id', $auth['id'])->orderByDesc('id')->paginate(10);

        MemberNoticeComment::query()->where('user_id', $auth['id'])->update(['read' => true]);

        $event = new CommentEvent();
        App::event()->dispatch($event, 'member.comment');

        $data = format_data($list, function ($item) use ($event, $auth) {
            return [
                'id' => $item->id,
                'comment_id' => $item->comment_id,
                'has_type' => $event->getName($item->has_type),
                'has_id' => $item->has_id,
                'cover' => $item->cover,
                'content' => $item->comment->content,
                'image' => $item->comment->image,
                'is_praise' => $item->comment->praises->where('user_id', $auth['id'])->first() ? true : false,
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
