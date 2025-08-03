<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Models\SystemUser;
use App\System\Service\Bulletin;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'admin', route: '/system/board')]
class Board
{
    #[Route(methods: 'GET', route: '')]
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getQueryParams();

        ["data" => $data, "meta" => $meta] = Bulletin::getList(SystemUser::class, $auth['id'], $params);

        // 获取统计信息
        $stats = Bulletin::getStats(SystemUser::class, $auth['id'], $params);
        $meta['stats'] = $stats;
        
        return send($response, 'ok', $data, $meta);
    }

    #[Route(methods: 'POST', route: '/{id}/read')]
    public function read(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        
        Bulletin::markRead(SystemUser::class, $auth['id'], (int)$args['id']);

        return send($response, "ok");
    }
}