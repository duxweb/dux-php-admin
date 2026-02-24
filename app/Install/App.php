<?php

declare(strict_types=1);

namespace App\Install;

use App\Install\Service\InstallService;
use Core\App as CoreApp;
use Core\App\AppExtend;
use Core\Bootstrap;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class App extends AppExtend
{
    public function init(Bootstrap $app): void
    {
        $service = new InstallService();

        // 未安装时，除 /install 开头路由外，全部强制跳安装页。
        $app->web->add(function (
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ) use ($service, $app): ResponseInterface {
            if ($service->isInstalled()) {
                return $handler->handle($request);
            }

            $path = $request->getUri()->getPath();
            if (preg_match('#^/install(?:/|$)#', $path)) {
                return $handler->handle($request);
            }

            return $app->web->getResponseFactory()
                ->createResponse(302)
                ->withHeader('Location', '/install/license');
        });
    }

    public function register(Bootstrap $app): void
    {
        CoreApp::loadTrans(__DIR__ . '/Langs', CoreApp::trans());

        $service = new InstallService();
        if ($service->isInstalled()) {
            return;
        }

        CoreApp::route()->get('web')->get(
            '',
            function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
                return $response->withStatus(302)->withHeader('Location', '/install/license');
            },
            'installEntry',
            1000
        );
    }

    public function boot(Bootstrap $app): void
    {
        $service = new InstallService();
        if (!$service->isInstalled()) {
            return;
        }

        if ($this->hasHomeRoute($app)) {
            return;
        }

        $app->web->get('/', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return sendTpl($response, dirname(__DIR__) . '/Install/Views/welcome.latte', [
                'title' => 'Welcome',
            ]);
        })->setName('welcome');
    }

    private function hasHomeRoute(Bootstrap $app): bool
    {
        $patterns = ['/', '', '{params:.*}', '/{params:.*}'];
        foreach ($app->web->getRouteCollector()->getRoutes() as $route) {
            if (!in_array($route->getPattern(), $patterns, true)) {
                continue;
            }
            if (in_array('GET', $route->getMethods(), true)) {
                return true;
            }
        }
        return false;
    }
}
