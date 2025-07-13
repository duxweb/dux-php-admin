<?php

namespace App\Member\Api;

use App\Member\Models\MemberFans;
use App\Member\Models\MemberNotice;
use App\Member\Models\MemberNoticeClass;
use App\Member\Models\MemberNoticeCollect;
use App\Member\Models\MemberNoticeComment;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/notice')]
#[Docs(name: '通知管理')]
class Notice
{

    #[Route(methods: 'GET', route: '')]
    #[Api(name: '通知列表', payloadExample: ['type' => 'system'])]
    #[Query(field: 'type', type: FieldEnum::STRING, name: '通知类型', required: false, desc: '通知类型筛选')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '通知列表', desc: '用户通知列表', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $params = $request->getQueryParams();
        $type = $params['type'] ?? '';
        $result = \App\Member\Service\Notice::list((int)$auth['id'], $type);
        return send($response, "ok", ...$result);
    }

    #[Route(methods: 'GET', route: '/class')]
    #[Api(name: '通知分类')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '分类列表', desc: '通知分类列表', children: [
        new ResultData(field: 'id', type: FieldEnum::INT, name: '分类ID', desc: '分类ID'),
        new ResultData(field: 'name', type: FieldEnum::STRING, name: '分类名称', desc: '分类名称'),
        new ResultData(field: 'unread', type: FieldEnum::INT, name: '未读数量', desc: '未读通知数量'),
        new ResultData(field: 'latest_notice', type: FieldEnum::OBJECT, name: '最新通知', required: false, desc: '最新通知信息')
    ], root: true)]
    public function class(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $params = $request->getQueryParams();
        $userId = (int)$auth['id'];

        $classList = MemberNoticeClass::query()->withCount([
            'notices' => function ($query) use ($userId) {
                $query->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('type', 1);
                })
                    ->whereNotExists(function ($query) use ($userId) {
                        $query->selectRaw(1)
                            ->from('member_notice_read')
                            ->whereColumn('member_notice_read.notice_id', 'member_notice.id')
                            ->where('member_notice_read.user_id', $userId);
                    });
            }
        ])
            ->with(['notices' => function ($query) use ($userId) {
                $query->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('type', 1);
                })
                    ->latest()
                    ->limit(1);
            }])
            ->get();

        $classList = $classList->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'unread' => $item->notices_count,
                'latest_notice' => $item->notices->first() ? [
                    'id' => $item->notices->first()->id,
                    'title' => $item->notices->first()->title,
                    'created_at' => $item->notices->first()->created_at,
                ] : null,
            ];
        })->toArray();

        return send($response, "ok", $classList);
    }

    #[Route(methods: 'POST', route: '/read')]
    #[Api(name: '标记已读', payloadExample: ['ids' => [1, 2, 3]])]
    #[Payload(field: 'ids', type: FieldEnum::ARRAY, name: '通知ID列表', desc: '需要标记为已读的通知ID数组')]
    public function read(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody();
        $ids = array_filter((array)$params['ids']);
        $auth = $request->getAttribute('auth');
        \App\Member\Service\Notice::read((int)$auth['id'], $ids);
        return send($response, "ok");
    }

    #[Route(methods: 'GET', route: '/num')]
    #[Api(name: '未读数量')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '未读统计', desc: '各类未读数量', children: [
        new ResultData(field: 'num', type: FieldEnum::INT, name: '未读通知', desc: '未读通知数量'),
        new ResultData(field: 'fans', type: FieldEnum::INT, name: '未读粉丝', desc: '未读粉丝数量'),
        new ResultData(field: 'collect', type: FieldEnum::INT, name: '未读收藏', desc: '未读收藏数量'),
        new ResultData(field: 'comment', type: FieldEnum::INT, name: '未读评论', desc: '未读评论数量')
    ], root: true)]
    public function num(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = (int)$request->getAttribute('auth')['id'];

        $unreadCount = MemberNotice::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('type', 1);
            })
            ->whereNotExists(function ($query) use ($userId) {
                $query->selectRaw(1)
                    ->from('member_notice_read')
                    ->whereColumn('member_notice_read.notice_id', 'member_notice.id')
                    ->where('member_notice_read.user_id', $userId);
            })
            ->count();

        return send($response, "ok", [
            'num' => $unreadCount,
            'fans' => MemberFans::query()->where('user_id', $userId)->where('read', false)->count(),
            'collect' => MemberNoticeCollect::query()->where('user_id', $userId)->where('read', false)->count(),
            'comment' => MemberNoticeComment::query()->where('user_id', $userId)->where('read', false)->count(),
        ]);
    }
}
