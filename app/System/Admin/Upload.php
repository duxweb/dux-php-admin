<?php

namespace App\System\Admin;

use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'admin', route: '/upload')]
class Upload extends \App\System\Extends\Upload
{

    #[Route(methods: 'GET', route: '')]
    public function sign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        return send($response, "ok", parent::uploadSign(filename: $params['name'], prefix: 'system'));
    }

    #[Route(methods: 'PUT', route: '')]
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = parent::uploadSave('admin', $request);
        return send($response, "ok", $data);
    }

    #[Route(methods: 'POST', route: '')]
    public function upload(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody();
        $manager = !!$params['manager'];
        $folder = $params['folder'] ?: null;
        $mime = $params['mime'];
        $data = parent::uploadLocal(
            hasType: 'admin',
            request: $request,
            manager: $manager,
            mime: $mime,
            folder: $folder,
        );
        return send($response, "ok", $data);
    }
}
