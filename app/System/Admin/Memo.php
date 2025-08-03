<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Models\SystemUser;
use App\System\Service\Memo as MemoService;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Handlers\ExceptionBusiness;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'admin', route: '/system/memo')]
class Memo
{
    #[Route(methods: 'GET', route: '')]
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getQueryParams();

        ["data" => $data, "meta" => $meta] = MemoService::getList(SystemUser::class, $auth['id'], $params);
        
        return send($response, 'ok', $data, $meta);
    }

    #[Route(methods: 'GET', route: '/{id}')]
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        
        $data = MemoService::getDetail((int)$args['id'], SystemUser::class, $auth['id']);
        
        return send($response, 'ok', $data);
    }

    #[Route(methods: 'POST', route: '')]
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getParsedBody();

        if (empty($params['title'])) {
            throw new ExceptionBusiness('请输入备忘录标题');
        }

        $data = MemoService::create(SystemUser::class, $auth['id'], $params);
        
        return send($response, 'ok', $data);
    }

    #[Route(methods: 'PUT', route: '/{id}')]
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getParsedBody();

        $data = MemoService::update((int)$args['id'], SystemUser::class, $auth['id'], $params);
        
        return send($response, 'ok', $data);
    }

    #[Route(methods: 'DELETE', route: '/{id}')]
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        
        MemoService::delete(SystemUser::class, $auth['id'], [(int)$args['id']]);
        
        return send($response, 'ok');
    }

    #[Route(methods: 'PATCH', route: '/{id}/complete')]
    public function complete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");
        $params = $request->getParsedBody();

        $isCompleted = $params['is_completed'] ?? true;

        MemoService::toggleComplete((int)$args['id'], SystemUser::class, $auth['id'], $isCompleted);

        return send($response, "ok");
    }

    #[Route(methods: 'GET', route: '/stats')]
    public function stats(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute("auth");

        $data = MemoService::getStats(SystemUser::class, $auth['id']);

        return send($response, "ok", $data);
    }
}