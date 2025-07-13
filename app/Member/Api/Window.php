<?php

namespace App\Member\Api;

use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/window')]
#[Docs(name: '窗口管理')]
class Window
{
    #[Route(methods: 'GET', route: '')]
    #[Api(name: '窗口列表')]
    #[ResultData(field: 'data', type: FieldEnum::ARRAY, name: '窗口列表', desc: '用户窗口列表', root: true)]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $result = \App\Member\Service\Window::list((int)$auth['id']);
        return send($response, "ok", $result);
    }

    #[Route(methods: 'POST', route: '/close')]
    #[Api(name: '关闭窗口')]
    #[Payload(field: 'ids', type: FieldEnum::ARRAY, name: '窗口ID列表', desc: '要关闭的窗口ID数组')]
    public function close(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody();
        $ids = array_filter((array)$params['ids']);
        $auth = $request->getAttribute('auth');
        \App\Member\Service\Window::close((int)$auth['id'], $ids);
        return send($response, "ok");
    }
}