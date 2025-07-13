<?php

namespace App\Member\Api;

use App\Member\Event\StatsEvent;
use Core\App;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/stats')]
#[Docs(name: '用户统计')]
class Stats
{
    #[Route(methods: 'GET', route: '')]
    #[Api(name: '用户统计数据')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '统计数据', desc: '用户统计数据', root: true)]
    public function data(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $event = new StatsEvent((int)$auth['id']);
        App::event()->dispatch($event, 'member.stats');
        return send($response, 'ok', $event->getMap());
    }

}