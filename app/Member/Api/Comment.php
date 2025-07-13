<?php

namespace App\Member\Api;

use App\Member\Event\CommentEvent;
use App\Member\Models\MemberComment;
use Core\App;
use Core\Auth\Auth;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Params;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionNotFound;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpUnauthorizedException;

#[RouteGroup(app: 'apiMember', route: '/member/comment')]
#[Docs(name: '评论管理')]
class Comment
{
    #[Route(methods: 'GET', route: '/{type}/{id}', auth: false)]
    #[Api(name: '评论列表', payloadExample: ['parent_id' => 0])]
    #[Query(field: 'parent_id', type: FieldEnum::INT, name: '父级评论ID', required: false, desc: '父级评论ID，获取子评论')]
    #[Params(field: 'type', type: FieldEnum::INT, name: '类型', required: false, desc: '评论模块类型标识')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '评论列表', desc: '评论列表数据', children: [
        new ResultData(field: 'id', type: FieldEnum::INT, name: '评论id', desc: '评论ID'),
        new ResultData(field: 'user_id', type: FieldEnum::INT, name: '用户ID', desc: '评论用户ID'),
        new ResultData(field: 'nickname', type: FieldEnum::STRING, name: '用户昵称', desc: '评论用户昵称'),
        new ResultData(field: 'avatar', type: FieldEnum::STRING, name: '用户头像', desc: '评论用户头像'),
        new ResultData(field: 'content', type: FieldEnum::STRING, name: '评论内容', desc: '评论文字内容'),
        new ResultData(field: 'praise', type: FieldEnum::INT, name: '点赞数', desc: '评论点赞数量'),
        new ResultData(field: 'is_praise', type: FieldEnum::BOOL, name: '是否点赞', desc: '当前用户是否点赞'),
        new ResultData(field: 'children_count', type: FieldEnum::INT, name: '子评论数', desc: '子评论数量')
    ], root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $params = $request->getQueryParams() ?: [];
        $parentId = $params['parent_id'];

        $event = new CommentEvent();
        App::event()->dispatch($event, 'member.comment');
        $type = $event->getMapType($args['type']);
        if (!$type) {
            throw new ExceptionNotFound();
        }

        $query = MemberComment::scoped(['has_type' => $type['class'], 'has_id' => $args['id']])
            ->with(['user', 'ancestors', 'children', 'descendants', 'praises'])
            ->where('status', 1);

        if (!$params['parent_id']) {
            $list = $query->whereIsRoot()->get()->toFlatTree();
        } else {
            $list = $query->descendantsOf($parentId)->toFlatTree($parentId);
        }

        $auth = (new Auth())->decode($request, 'member');
        $userId = $auth['id'];

        $data = format_data($list, function ($item) use ($userId, $parentId) {
            $itemData = $this->format($item, $userId);
            if (!$parentId) {
                $itemData['children_last'] = $item->children[0] ? $this->format($item->children[0], $userId) : null;
            }
            $itemData['children_count'] = $item->descendants->count();
            return $itemData;
        });

        return send($response, "ok", ...$data);
    }

    private function format($item, $userId)
    {
        return [
            "id" => $item->id,
            'user_id' => $item->user_id,
            'parent_id' => $item->parent_id,
            'nickname' => $item->user->nickname,
            'avatar' => $item->user->avatar,
            'content' => $item->content,
            'image' => $item->image,
            'praise' => $item->praise,
            'is_praise' => $item->praises->where('user_id', $userId)->first() ? true : false,
            'parent' => $item->ancestors[1] ? [
                'id' => $item->ancestors[1]->user->id,
                'nickname' => $item->ancestors[1]->user->nickname,
                'avatar' => $item->ancestors[1]->user->avatar,
                'parent_id' => $item->ancestors[1]->id,
            ] : null,
            'time' => $item->created_at,
            'country' => $item->country,
            'province' => $item->province,
            'city' => $item->city,
        ];
    }


    #[Route(methods: 'POST', route: '/{type}/{id}')]
    #[Api(name: '发表评论', payloadExample: ['content' => '评论内容', 'image' => 'http://example.com/image.jpg', 'reply_id' => 1])]
    #[Payload(field: 'content', type: FieldEnum::STRING, name: '评论内容', desc: '评论文字内容')]
    #[Payload(field: 'image', type: FieldEnum::STRING, name: '评论图片', required: false, desc: '评论图片URL')]
    #[Payload(field: 'reply_id', type: FieldEnum::INT, name: '回复评论ID', required: false, desc: '回复的评论ID')]
    public function push(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody() ?: [];
        $auth = $request->getAttribute('auth');
        $userId = $auth['id'];
        if (!$userId) {
            throw new HttpUnauthorizedException($request);
        }

        $event = new CommentEvent();
        App::event()->dispatch($event, 'member.comment');
        $type = $event->getMapType($args['type']);
        if (!$type) {
            throw new ExceptionNotFound();
        }
        $data = Validator::parser($data, [
            "content" => ["required", "请输入评论内容"],
        ]);

        $ip = $request->getHeaderLine('X-Real-IP');
        // 地址处理
        try {
            $address = App::geo()?->search($ip) ?: '';
            [$country, $null, $province, $city] = explode('|', $address);
        } catch (\Throwable $e) {
            $country = null;
        }

        App::db()->getConnection()->beginTransaction();
        try {
            \App\Member\Service\Comment::push(
                userId: $userId,
                hasType: $type['class'],
                hasId: (int)$args['id'],
                content: [
                    'content' => $data->content ?: '',
                    'image' => $data->image ?: '',
                ],
                replyId: (int)$data->reply_id,
                address: [
                    'ip' => $ip,
                    'country' => $country,
                    'province' => $province,
                    'city' => $city,
                ],
                comment: $type['comment']
            );
            App::db()->getConnection()->commit();
        } catch (\Exception $e) {
            App::db()->getConnection()->rollBack();
            throw $e;
        }
        return send($response, "ok");
    }
}