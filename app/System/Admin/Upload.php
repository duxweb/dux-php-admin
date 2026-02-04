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
        $manager = filter_var($params['manager'] ?? false, FILTER_VALIDATE_BOOLEAN);
        return send($response, "ok", parent::uploadSign(
            filename: (string)($params['name'] ?? ''),
            mime: (string)($params['mime'] ?? ''),
            size: isset($params['size']) ? (int)$params['size'] : 0,
            driver: (string)($params['driver'] ?? ''),
            prefix: 'system',
            manager: $manager,
            hasType: 'admin',
            folder: isset($params['folder']) ? (int)$params['folder'] : null,
        ));
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
        $data = parent::uploadStorage(
            hasType: 'admin',
            request: $request,
            manager: $manager,
            mime: $mime,
            folder: $folder,
        );
        return send($response, "ok", $data);
    }
}
