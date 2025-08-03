<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Models\SystemNotice;
use App\System\Models\SystemUser;
use App\System\Service\Notice as NoticeService;
use App\System\Service\SystemNotice as ServiceSystemNotice;
use Core\Handlers\ExceptionBusiness;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'admin', route: '/system/notice', name: 'system.notice')]
class Notice
{
    #[Route(methods: 'GET', route: '', name: 'list')]
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getQueryParams();
        
        // 获取当前用户的通知
        ["data" => $data, "meta" => $meta] = NoticeService::getList(
            $params, 
            SystemUser::class, 
            $auth['id']
        );
        
        return send($response, 'ok', $data, $meta);
    }

    #[Route(methods: 'GET', route: '/stats', name: 'stats')]
    public function stats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $id = (int)$auth['id'];
        $data = NoticeService::getStats(SystemUser::class, $id);
        return send($response, 'ok', $data);
    }

    #[Route(methods: 'GET', route: '/{id}', name: 'show')]
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        
        $data = NoticeService::getDetail(
            (int)$args['id'], 
            SystemUser::class, 
            $auth['id']
        );
        
        if (!$data) {
            throw new ExceptionBusiness('通知不存在');
        }
        
        return send($response, 'ok', $data);
    }

    #[Route(methods: 'POST', route: '', name: 'batch')]
    public function batch(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];

        switch ($data['type']) {
            case 'read':
                $id = $data['id'];
                NoticeService::markRead(SystemUser::class, $userId, [$id]);
                break;
            case 'all_read':
                NoticeService::markRead(SystemUser::class, $userId);
                break;
            case 'delete':
                $id = $data['id'];
                NoticeService::delete(SystemUser::class, $userId, [$id]);
                break;
        }

        return send($response, "ok");
    }
}