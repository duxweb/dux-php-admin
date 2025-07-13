<?php

namespace App\Member\Api;

use App\Member\Models\MemberBlacklist;
use App\Member\Models\MemberUser;
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

#[RouteGroup(app: 'apiMember', route: '/member/blacklist')]
#[Docs(name: '黑名单管理')]
class Blacklist
{
    #[Route(methods: 'GET', route: '')]
    #[Api(name: '黑名单列表')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '黑名单列表', desc: '用户黑名单数据', children: [
        new ResultData(field: 'id', type: FieldEnum::INT, name: '用户ID', desc: '被拉黑用户ID'),
        new ResultData(field: 'nickname', type: FieldEnum::STRING, name: '用户昵称', desc: '被拉黑用户昵称'),
        new ResultData(field: 'avatar', type: FieldEnum::STRING, name: '用户头像', desc: '被拉黑用户头像'),
        new ResultData(field: 'info', type: FieldEnum::OBJECT, name: '用户信息', desc: '被拉黑用户详细信息')
    ], root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        $userList = MemberBlacklist::query()->where('user_id', $userId)->orderByDesc('id')->paginate(20);

        $data = format_data($userList, function ($item) {
            return [
                'id' => $item->user_id,
                'nickname' => $item->fans->nickname,
                'avatar' => $item->fans->avatar,
                'info' => $item->fans->info
            ];
        });

        return send($response, "ok", ...$data);
    }

    #[Route(methods: 'POST', route: '/{id}')]
    #[Api(name: '拉黑/取消拉黑')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '用户ID', desc: '要拉黑的用户ID')]
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        if (!$userId) {
            throw new HttpUnauthorizedException($request);
        }
        $userInfo = MemberUser::query()->find($args['id']);
        if (!$userInfo) {
            throw new ExceptionNotFound();
        }
        if ($userId == $userInfo->id) {
            throw new ExceptionBusiness("不能拉黑自己");
        }

        $info = MemberBlacklist::query()->where('user_id', $userId)->where('blacklist_user_id', $args['id'])->first();
        if ($info) {
            MemberBlacklist::query()->where('id', $info->id)->delete();
        }else {
            MemberBlacklist::query()->create([
                'user_id' => $userId,
                'blacklist_user_id' => $args['id']
            ]);
        }

        return send($response, "黑名单成功");
    }

    #[Route(methods: 'DELETE', route: '/{id}')]
    #[Api(name: '取消拉黑')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '用户ID', desc: '要取消拉黑的用户ID')]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody() ?: [];
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        MemberBlacklist::query()->where([
            'user_id' => $userId,
            'blacklist_user_id' => $args['id']
        ])->delete();
        return send($response, "取消黑名单成功");
    }
}