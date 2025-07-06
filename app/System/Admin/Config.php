<?php

namespace App\System\Admin;

use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


#[Resource(app: 'admin',  route: '/system/config', name: 'system.config', actions: false)]
class Config
{

    #[Action(methods: 'GET', route: '', name: 'info')]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $config = \App\System\Service\Config::getJsonValue("system");

        return send($response, "ok", $config ?: (object) []);
    }

    #[Action(methods: 'PUT', route: '', name: 'save')]
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        \App\System\Service\Config::setValue("system", $data);
        return send($response, "ok");
    }
}
