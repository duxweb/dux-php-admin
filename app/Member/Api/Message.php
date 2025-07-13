<?php

namespace App\Member\Api;

use App\Member\Models\MemberMessage;
use App\Member\Models\MemberUser;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Validator\Validator;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/message')]
#[Docs(name: '私信消息')]
class Message
{
    #[Route(methods: 'GET', route: '/list')]
    #[Api(name: '消息列表', payloadExample: ['page' => 1, 'limit' => 20])]
    #[Query(field: 'page', type: FieldEnum::INT, name: '页码', required: false, desc: '分页页码，默认1')]
    #[Query(field: 'limit', type: FieldEnum::INT, name: '每页数量', required: false, desc: '每页数量，默认20')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '消息列表', desc: '消息会话列表', root: true)]
    #[ResultData(field: 'meta', type: FieldEnum::OBJECT, name: '分页信息', desc: '分页相关信息', children: [
        new ResultData(field: 'total', type: FieldEnum::INT, name: '总数', desc: '会话总数'),
        new ResultData(field: 'page', type: FieldEnum::INT, name: '当前页', desc: '当前页码'),
        new ResultData(field: 'limit', type: FieldEnum::INT, name: '每页数量', desc: '每页数量')
    ])]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $params = $request->getQueryParams();
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        $userId = $auth['id'];
        
        // 获取最新的消息会话
        $conversations = MemberMessage::query()
            ->selectRaw('
                CASE 
                    WHEN from_user_id = ? THEN to_user_id 
                    ELSE from_user_id 
                END as other_user_id,
                MAX(created_at) as last_message_time,
                COUNT(*) as message_count,
                SUM(CASE WHEN to_user_id = ? AND is_read = 0 THEN 1 ELSE 0 END) as unread_count
            ', [$userId, $userId])
            ->where(function (Builder $query) use ($userId) {
                $query->where('from_user_id', $userId)
                      ->orWhere('to_user_id', $userId);
            })
            ->groupByRaw('
                CASE 
                    WHEN from_user_id = ? THEN to_user_id 
                    ELSE from_user_id 
                END
            ', [$userId])
            ->orderBy('last_message_time', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $data = format_data($conversations, function ($conversation) use ($userId) {
            $otherUser = MemberUser::find($conversation->other_user_id);
            if (!$otherUser) {
                return null;
            }

            $lastMessage = MemberMessage::query()
                ->where(function (Builder $query) use ($userId, $conversation) {
                    $query->where('from_user_id', $userId)
                          ->where('to_user_id', $conversation->other_user_id);
                })
                ->orWhere(function (Builder $query) use ($userId, $conversation) {
                    $query->where('from_user_id', $conversation->other_user_id)
                          ->where('to_user_id', $userId);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            return [
                'user_id' => $otherUser->id,
                'nickname' => $otherUser->nickname,
                'avatar' => $otherUser->avatar,
                'last_message' => $lastMessage ? [
                    'type' => $lastMessage->type,
                    'content' => $lastMessage->content,
                    'created_at' => $lastMessage->created_at->format('Y-m-d H:i:s')
                ] : null,
                'unread_count' => $conversation->unread_count,
                'message_count' => $conversation->message_count,
                'last_message_time' => $conversation->last_message_time
            ];
        });

        return send($response, 'ok', ...$data);
    }

    #[Route(methods: 'GET', route: '/history')]
    #[Api(name: '消息记录', payloadExample: ['user_id' => 123, 'page' => 1, 'limit' => 50])]
    #[Query(field: 'user_id', type: FieldEnum::INT, name: '用户ID', desc: '对方用户ID')]
    #[Query(field: 'page', type: FieldEnum::INT, name: '页码', required: false, desc: '分页页码，默认1')]
    #[Query(field: 'limit', type: FieldEnum::INT, name: '每页数量', required: false, desc: '每页数量，默认50')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '消息记录', desc: '双方消息记录', root: true)]
    #[ResultData(field: 'meta', type: FieldEnum::OBJECT, name: '分页信息', desc: '分页相关信息', children: [
        new ResultData(field: 'total', type: FieldEnum::INT, name: '总数', desc: '消息总数'),
        new ResultData(field: 'page', type: FieldEnum::INT, name: '当前页', desc: '当前页码'),
        new ResultData(field: 'limit', type: FieldEnum::INT, name: '每页数量', desc: '每页数量')
    ])]
    public function history(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $params = $request->getQueryParams();
        
        $data = Validator::parser($params, [
            'user_id' => ['required', 'integer', '请输入用户ID'],
        ]);

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $userId = $auth['id'];
        $otherUserId = $data->user_id;

        // 检查对方用户是否存在
        $otherUser = MemberUser::find($otherUserId);
        if (!$otherUser) {
            throw new ExceptionBusiness("用户不存在");
        }

        // 获取消息记录
        $messages = MemberMessage::query()
            ->where(function (Builder $query) use ($userId, $otherUserId) {
                $query->where('from_user_id', $userId)
                      ->where('to_user_id', $otherUserId);
            })
            ->orWhere(function (Builder $query) use ($userId, $otherUserId) {
                $query->where('from_user_id', $otherUserId)
                      ->where('to_user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        // 标记来自对方的消息为已读
        MemberMessage::query()
            ->where('from_user_id', $otherUserId)
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $data = format_data($messages, function ($message) use ($userId) {
            $transformedMessage = $message->transform();
            $transformedMessage['is_mine'] = $message->from_user_id == $userId;
            return $transformedMessage;
        });

        return send($response, 'ok', ...$data);
    }

    #[Route(methods: 'POST', route: '/send')]
    #[Api(name: '发送消息', payloadExample: ['to_user_id' => 123, 'type' => 'text', 'content' => '消息内容'])]
    #[Payload(field: 'to_user_id', type: FieldEnum::INT, name: '接收者ID', desc: '接收消息的用户ID')]
    #[Payload(field: 'type', type: FieldEnum::STRING, name: '消息类型', desc: '消息类型：text-文字，image-图片')]
    #[Payload(field: 'content', type: FieldEnum::STRING, name: '消息内容', desc: '消息内容，文字或图片URL')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '发送结果', desc: '消息发送结果', children: [
        new ResultData(field: 'id', type: FieldEnum::INT, name: '消息ID', desc: '消息ID'),
        new ResultData(field: 'created_at', type: FieldEnum::STRING, name: '发送时间', desc: '消息发送时间')
    ], root: true)]
    public function send(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $data = Validator::parser($request->getParsedBody(), [
            'to_user_id' => ['required', 'integer', '请输入接收者ID'],
            'type' => ['required', 'in:text,image', '消息类型只能是text或image'],
            'content' => ['required', '请输入消息内容'],
        ]);

        $userId = $auth['id'];
        $toUserId = $data->to_user_id;

        // 检查接收者是否存在
        $toUser = MemberUser::query()->find($toUserId);
        if (!$toUser) {
            throw new ExceptionBusiness("接收者不存在");
        }

        // 检查是否给自己发消息
        if ($userId == $toUserId) {
            throw new ExceptionBusiness("不能给自己发送消息");
        }

        // 验证消息内容
        if ($data->type === 'text' && empty(trim($data->content))) {
            throw new ExceptionBusiness("文字消息内容不能为空");
        }

        if ($data->type === 'image' && !filter_var($data->content, FILTER_VALIDATE_URL)) {
            throw new ExceptionBusiness("图片消息内容必须是有效的URL");
        }

        // 创建消息
        $message = MemberMessage::create([
            'from_user_id' => $userId,
            'to_user_id' => $toUserId,
            'type' => $data->type,
            'content' => $data->content,
            'is_read' => false
        ]);

        return send($response, '消息发送成功', [
            'id' => $message->id,
            'created_at' => $message->created_at->format('Y-m-d H:i:s')
        ]);
    }

    #[Route(methods: 'POST', route: '/read')]
    #[Api(name: '标记已读', payloadExample: ['user_id' => 123])]
    #[Payload(field: 'user_id', type: FieldEnum::INT, name: '用户ID', desc: '标记来自该用户的消息为已读')]
    public function read(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $data = Validator::parser($request->getParsedBody(), [
            'user_id' => ['required', 'integer', '请输入用户ID'],
        ]);

        $userId = $auth['id'];
        $fromUserId = $data->user_id;

        // 标记来自指定用户的消息为已读
        MemberMessage::query()
            ->where('from_user_id', $fromUserId)
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return send($response, '标记已读成功');
    }

    #[Route(methods: 'GET', route: '/unread')]
    #[Api(name: '未读数量')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '未读统计', desc: '未读消息统计', children: [
        new ResultData(field: 'total', type: FieldEnum::INT, name: '总未读数', desc: '总的未读消息数量'),
        new ResultData(field: 'conversations', type: FieldEnum::INT, name: '未读会话数', desc: '有未读消息的会话数量')
    ], root: true)]
    public function unread(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = $auth['id'];

        // 总未读消息数
        $total = MemberMessage::query()
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->count();

        // 有未读消息的会话数
        $conversations = MemberMessage::query()
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->distinct('from_user_id')
            ->count();

        return send($response, 'ok', [
            'total' => $total,
            'conversations' => $conversations
        ]);
    }
}