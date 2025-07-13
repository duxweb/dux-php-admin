<?php

namespace App\System\Api;

use App\System\Models\SystemSetting;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'api', route: '')]
#[Docs(name: '系统设置')]
class Setting
{

    #[Route(methods: 'GET', route: '/system/setting')]
    #[Api(name: '获取系统设置', payloadExample: [])]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '系统设置', desc: '系统公开设置项键值对', root: true)]
    public function setting(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $data = SystemSetting::query()->where('public', true)->get()->reduce(function ($carry, $item) {
            $carry[$item->key] = $item->format_value;
            return $carry;
        }, []);


        return send($response, 'ok', $data);
    }
}
