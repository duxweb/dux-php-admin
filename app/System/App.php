<?php

declare(strict_types=1);

namespace App\System;

use App\System\Middleware\OperateMiddleware;
use App\System\Models\SystemApi;
use App\System\Service\SchedulerService;
use App\System\Models\SystemUser;
use Core\Api\ApiMiddleware;
use Core\App as CoreApp;
use Core\App\AppExtend;
use Core\Auth\AuthMiddleware;
use Core\Bootstrap;
use Core\Handlers\ExceptionBusiness;
use Core\Permission\PermissionMiddleware;
use Core\Resources\Resource;
use Core\Route\Route;
use Core\Scheduler\SchedulerGenEvent;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class App extends AppExtend
{

    public function init(Bootstrap $app): void
    {
        // 初始化资源
        CoreApp::resource()->set(
            "admin",
            (new Resource(
                'admin',
                '/admin'
            ))->addAuthMiddleware(
                new OperateMiddleware(SystemUser::class),
                new PermissionMiddleware("admin", SystemUser::class),
                new AuthMiddleware("admin")
            )
        );

        CoreApp::route()->set("web", new Route(""));


        CoreApp::route()->set(
            "api",
            new Route(
                pattern: "/api",
                middleware: new ApiMiddleware(function ($id) {
                    $apiInfo = SystemApi::query()->where('secret_id', $id)->firstOr(function () {
                        throw new ExceptionBusiness('Signature authorization failed', 402);
                    });
                    return $apiInfo->secret_key;
                })
            ),
        );
    }

    public function register(Bootstrap $app): void
    {
        CoreApp::event()->addListener('scheduler.gen', static function (SchedulerGenEvent $event) {
            $event->setData(SchedulerService::buildJobs());
        });
    }
}
