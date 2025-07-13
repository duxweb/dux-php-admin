<?php

namespace App\Member\Api;

use App\Member\Models\MemberUnion;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\Query;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Docs(name: '第三方登录')]
class Union
{

    #[Route(methods: 'POST', route: '/member/oauth/token', app: 'api')]
    #[Api(name: '获取第三方令牌')]
    #[Payload(field: 'type', type: FieldEnum::STRING, name: '第三方类型', desc: '第三方登录类型')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '授权码', desc: '第三方授权码')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '令牌信息', desc: '第三方令牌信息', children: [
        new ResultData(field: 'token', type: FieldEnum::STRING, name: '令牌', desc: '第三方令牌')
    ], root: true)]
    public function token(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getParsedBody(), [
            "type" => ["required", "三方类型不存在"],
            "code" => ["required", "三方登录code"],
        ]);
        $token = \App\Member\Service\Union::get($data->type, $data->code, $data->params ?: [], $data->app_id);
        return send($response, 'ok', [
            'token' => $token
        ]);
    }

    #[Route(methods: 'POST', route: '/member/oauth/login', app: 'api')]
    #[Api(name: '第三方登录')]
    #[Payload(field: 'token', type: FieldEnum::STRING, name: '第三方令牌', desc: '第三方登录令牌')]
    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody() ?: [];
        $data = Validator::parser($params, [
            "token" => ["required", "请传递token"],
        ]);
        return send($response, "ok", \App\Member\Service\Union::Login((string)$data->token, $data));
    }

    #[Route(methods: 'POST', route: '/member/oauth/bind', app: 'apiMember')]
    #[Api(name: '绑定第三方账户')]
    #[Payload(field: 'token', type: FieldEnum::STRING, name: '第三方令牌', desc: '第三方登录令牌')]
    public function bind(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = Validator::parser($request->getParsedBody(), [
            "token" => ["required", "请传递token"],
        ]);
        $auth = $request->getAttribute("auth", []);
        \App\Member\Service\Union::bind($data->token, (int)$auth["id"]);
        return send($response, '绑定成功');
    }

    #[Route(methods: 'GET', route: '/member/oauth', app: 'apiMember')]
    #[Api(name: '第三方账户信息')]
    #[Query(field: 'type', type: FieldEnum::STRING, name: '第三方类型', desc: '第三方登录类型')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '第三方信息', desc: '第三方账户信息', children: [
        new ResultData(field: 'union_id', type: FieldEnum::STRING, name: '联合ID', desc: '第三方平台联合ID'),
        new ResultData(field: 'open_id', type: FieldEnum::STRING, name: '开放ID', desc: '第三方平台开放ID')
    ], root: true)]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $params = $request->getQueryParams();

        $info = MemberUnion::query()->where('type', $params['type'])->where('user_id', $auth['id'])->orderByDesc('updated_at')->first();

        return send($response, 'ok', [
            'union_id' => $info->union_id,
            'open_id' => $info->open_id,
        ]);
    }

    #[Route(methods: 'POST', route: '/member/oauth/has', app: 'apiMember')]
    #[Api(name: '关联第三方账户')]
    #[Payload(field: 'type', type: FieldEnum::STRING, name: '第三方类型', desc: '第三方登录类型')]
    #[Payload(field: 'code', type: FieldEnum::STRING, name: '授权码', desc: '第三方授权码')]
    public function has(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $data = Validator::parser($request->getParsedBody(), [
            "type" => ["required", "三方类型不存在"],
            "code" => ["required", "三方登录code"],
        ]);
        \App\Member\Service\Union::has((int)$auth['id'], $data->type, $data->code, $data->params ?: []);
        return send($response, '关联成功');
    }

}