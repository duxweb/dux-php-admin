<?php

declare(strict_types=1);

namespace App\System\Handler;

use App\System\Models\SystemMenu;
use Core\Handlers\ExceptionBusiness;
use Core\Resources\Attribute\Action;
use Core\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ManageHandler
{
    protected string $app = 'admin';

    #[Action(methods: 'GET', route: '/router')]
    public function router(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $menu = SystemMenu::getMenu($this->app);
        return send($response, "ok", $menu);
    }

    #[Action(methods: 'POST', route: '/static')]
    public function static(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody();

        $rules = [
            'path' => ['required', '请传递模板路径']
        ];

        $data = Validator::parser($params, $rules);

        [$content, $ext, $error] = $this->getFile($data->path);
        if ($error) {
            throw new ExceptionBusiness($error);
        }

        return send($response, "ok", [
            'path' => $params['path'],
            'type' => $ext,
            'content' => $content
        ]);
    }

    private function getFile(string $route): array
    {
        $file = $this->routeToFile($route);

        // 尝试读取.vue文件
        $vuePath = app_path($file . ".vue");
        if (file_exists($vuePath)) {
            return [file_get_contents($vuePath), '.vue'];
        }

        // 尝试读取.json文件
        $jsonPath = app_path($file . ".json");
        if (file_exists($jsonPath)) {
            return [json_decode(file_get_contents($jsonPath), true), '.json'];
        }

        // 尝试读取.js文件
        $jsPath = app_path($file . ".js");
        if (file_exists($jsPath)) {
            return [file_get_contents($jsPath), '.mjs'];
        }

        // 尝试读取.ts文件
        $tsPath = app_path($file . ".ts");
        if (file_exists($tsPath)) {
            return [file_get_contents($tsPath), '.mjs'];
        }

        // 尝试读取原始文件
        $filePath = app_path($file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (file_exists($filePath)) {
            if ($ext === 'php') {
                throw new ExceptionBusiness('文件类型不支持');
            }
            return [file_get_contents($filePath), $ext, null];
        }

        throw new ExceptionBusiness('文件不存在');
    }

    private function routeToFile(string $path): string
    {
        $path = str_replace(['..', './'], '', $path);
        $path = ltrim($path, '/');
        if (preg_match('/[<>:"\\|?*]/', $path)) {
            throw new ExceptionBusiness('路径包含非法字符');
        }

        $parts = explode('/', $path);

        if (count($parts) < 2) {
            return $path;
        }

        array_splice($parts, 1, 0, ucfirst($this->app));

        return implode('/', $parts);
    }
}
