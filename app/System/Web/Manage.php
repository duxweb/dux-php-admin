<?php

namespace App\System\Web;

use App\System\Models\SystemStorage;
use App\System\Service\Config;
use Core\App;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'web', route: '/manage')]
class Manage
{
    #[Route(methods: 'GET', route: '')]
    public function location(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $response->withStatus(302)->withHeader('Location', '/manage/');
    }

    #[Route(methods: 'GET', route: '/')]
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = json_decode(file_get_contents(public_path('/static/web/.vite/manifest.json')) ?: '', true);
        $vite = App::config('use')->get('vite', []);
        $lang = App::config('use')->get('app.lang', 'en-US');

        $systemConfig = Config::getJsonValue('system');
        try {
            $storage = SystemStorage::query()->find($systemConfig['storage']);
        } catch (\Exception $e) {
            $storage = null;
        }

        $themeConfig = App::config('use')->get('theme', []);

        $themeConfig['logo'] = $systemConfig['logo_light'] ?: $themeConfig['logo'];
        $themeConfig['darkLogo'] = $systemConfig['logo_dark'] ?: $themeConfig['darkLogo'];
        $themeConfig['appLogo'] = $systemConfig['app_logo_light'] ?: $themeConfig['appLogo'];
        $themeConfig['appDarkLogo'] = $systemConfig['app_logo_dark'] ?: $themeConfig['appDarkLogo'];

        $assign = [
            "title" => $systemConfig['title'] ?: App::config('use')->get('app.name'),
            "lang" => $lang,
            'vite' => [
                'dev' => (bool)$vite['dev'],
                'port' => $vite['port'] ?: 5173,
            ],
            'manifest' => [
                'js' => $data['main.ts']['file'],
                'css' => $data['style.css']['file'],
            ],
            'config' => [
                'defaultManage' => 'admin',
                'theme' => [
                    'logo' => null,
                    'darkLogo' => null,
                    "appLogo" => null,
                    "appDarkLogo" => null,
                    'banner' => null,
                    'darkBanner' => null,
                    'layout' => 'app',
                    ...$themeConfig,
                ],
                'copyright' => $systemConfig['copyright'] ?: App::config('use')->get('app.copyright'),
                'manage' => [
                    [
                        'name' => 'admin',
                        'title' => App::config('use')->get('app.name'),
                        'description' => '',
                        'routePrefix' => '/admin',
                        'apiBasePath' => '/admin',
                        'apiRoutePath' => '/router',
                        'userMenus' => [
                            [
                                'key' => 'board',
                                "label" => "我的公告",
                                "icon" => "i-tabler:pinned",
                                "path" => "system/board",
                            ],
                            [
                                'key' => 'notice',
                                "label" => "我的通知",
                                "icon" => "i-tabler:bell",
                                "path" => "system/notice",
                            ],
                            [
                                'key' => ' memo',
                                "label" => "我的备忘",
                                "icon" => "i-tabler:message",
                                "path" => "system/memo",
                            ],
                            [
                                'key' => 'setting',
                                "label" => "个人资料",
                                "icon" => "i-tabler:settings",
                                "path" => "system/profile",
                            ],
                        ],
                        'upload' => [
                            'driver' => $storage ? $storage->type : 'local',
                        ],
                        'notice' => [
                            'status' => true,
                            'path' => 'system/notice',
                            'route' => 'notice'
                        ],
                    ],
                ],
            ],
        ];

        return sendTpl($response, dirname(__DIR__) . "/Views/manage.latte", $assign);
    }
}
