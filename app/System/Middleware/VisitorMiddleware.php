<?php

namespace App\System\Middleware;

use App\System\Service\Visitor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class VisitorMiddleware
{

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $url = $request->getUri();
        $path = $url->getPath();

        $hasInstallLock = is_file(data_path('/install.lock'));
        $pathContainsInstall = str_contains($path, '/install');

        if (!$hasInstallLock && !$pathContainsInstall) {
            return (new \Slim\Psr7\Response(302))->withHeader('Location', '/install')->withStatus(302);
        }

        if ($hasInstallLock && $pathContainsInstall) {
            return (new \Slim\Psr7\Response(302))->withHeader('Location', '/')->withStatus(302);
        }

        if (str_contains($path, "/static")) {
            return $handler->handle($request);
        }

        if (!$pathContainsInstall) {
            Visitor::increment($request, 'common');
        }

        return $handler->handle($request);
    }
}
