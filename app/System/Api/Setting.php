<?php

namespace App\System\Api;

use App\System\Models\SystemSetting;
use Core\Route\Attribute\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Setting
{

    #[Route(methods: 'GET', route: '/system/setting', app: 'api')]
    public function setting(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $data = SystemSetting::query()->where('public', true)->get()->reduce(function ($carry, $item) {
            $carry[$item->key] = $item->format_value;
            return $carry;
        }, []);


        return send($response, 'ok', $data);
    }
}
