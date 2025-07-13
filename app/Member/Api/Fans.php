<?php

namespace App\Member\Api;

use App\Member\Event\FansEvent;
use App\Member\Models\MemberFans;
use App\Member\Models\MemberUser;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Params;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Handlers\ExceptionNotFound;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpUnauthorizedException;

#[RouteGroup(app: 'apiMember', route: '/member/fans')]
#[Docs(name: '粉丝管理')]
class Fans
{
    #[Route(methods: 'GET', route: '')]
    #[Api(name: '粉丝列表')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '粉丝列表', desc: '用户粉丝列表', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        $userList = MemberFans::query()->where('user_id', $userId)->orderByDesc('id')->paginate(20);

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

    #[Route(methods: 'GET', route: '/concern')]
    #[Api(name: '关注列表')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '关注列表', desc: '用户关注列表', root: true)]
    public function concern(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        $userList = MemberFans::query()->where('fans_user_id', $userId)->orderByDesc('id')->paginate(20);

        $data = format_data($userList, function ($item) {
            return [
                'id' => $item->user_id,
                'nickname' => $item->user->nickname,
                'avatar' => $item->user->avatar,
                'info' => $item->user->info
            ];
        });

        return send($response, "ok", ...$data);
    }

    #[Route(methods: 'POST', route: '/{id}')]
    #[Api(name: '关注/取消关注')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '用户ID', desc: '要关注的用户ID')]
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
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
            throw new ExceptionBusiness("不能关注自己");
        }

        $event = new FansEvent();
        App::event()->dispatch($event, 'member.fans');

        $info = MemberFans::query()->where('user_id', $args['id'])->where('fans_user_id', $userId)->first();

        if ($info) {
            MemberFans::query()->where('id', $info->id)->delete();
            $status = false;
        }else {
            MemberFans::query()->create([
                'user_id' => $args['id'],
                'fans_user_id' => $userId
            ]);
            $status = true;
        }

        foreach ($event->maps as $vo) {
            if (!$vo['callback']) {
                continue;
            }
            $vo['callback']($userId, (int)$args['id'], $status);
        }

        return send($response, "关注成功");
    }

    #[Route(methods: 'DELETE', route: '/{id}')]
    #[Api(name: '取消关注')]
    #[Params(field: 'id', type: FieldEnum::INT, name: '用户ID', desc: '要取消关注的用户ID')]
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody() ?: [];
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        MemberFans::query()->where([
            'user_id' => $args['id'],
            'fans_user_id' => $userId
        ])->delete();
        return send($response, "取消关注成功");
    }
}