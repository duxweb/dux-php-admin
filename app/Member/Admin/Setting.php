<?php

namespace App\Member\Admin;

use App\System\Service\Config;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


#[Resource(app: 'admin',  route: '/member/setting', name: 'member.setting', actions: false)]
class Setting {

    #[Action(methods: 'GET', route: '')]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $config = \App\System\Service\Config::getJsonValue("member", []);
        return send($response, "ok", $config);
    }

    #[Action(methods: 'PUT', route: '')]
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $data = $request->getParsedBody();
        Config::setValue("member", $data);
        return send($response, "会员设置保存成功");
    }
}