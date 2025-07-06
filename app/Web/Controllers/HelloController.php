<?php
namespace App\Web\Controllers;

use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'web', route: '/test')]
class HelloController
{

    #[Route(methods: 'GET', route: '')]
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return sendText($response, 'Hello <a href="/hello">Dux Lite</a>');
    }

    #[Route(methods: 'GET', route: '/hello')]
    public function hello(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return sendText($response,'Dux Lite');
    }
}