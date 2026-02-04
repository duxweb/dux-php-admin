<?php

declare(strict_types=1);

namespace App\System\Handler;

use App\System\Models\SystemArea;
use App\System\Models\SystemMenu;
use Core\Handlers\ExceptionBusiness;
use Core\Resources\Attribute\Action;
use Core\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ManageHandler
{
    protected string $app = 'admin';



    #[Action(methods: 'GET', route: '/area')]
    public function area(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        $level = $params['level'] ?: 0;
        $name = $params['name'];
        $model = new SystemArea();
        $info = $model->query()->where('name', $name)->where('level', $level)->first();
        $data = $model->query()->where('level', $level + 1)->where('parent_code', $name ? $info['code'] : 0)->get(["name as value", "name as label", "leaf"])->toArray();
        return send($response, 'ok', $data);
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

        foreach ($this->buildCandidates($file) as $candidate) {
            // 尝试读取.vue文件
            $vuePath = app_path($candidate . ".vue");
            if (file_exists($vuePath)) {
                return [file_get_contents($vuePath), '.vue'];
            }

            // 尝试读取.json文件
            $jsonPath = app_path($candidate . ".json");
            if (file_exists($jsonPath)) {
                return [json_decode(file_get_contents($jsonPath), true), '.json'];
            }

            // 尝试读取.js文件
            $jsPath = app_path($candidate . ".js");
            if (file_exists($jsPath)) {
                return [file_get_contents($jsPath), '.mjs'];
            }

            // 尝试读取.ts文件
            $tsPath = app_path($candidate . ".ts");
            if (file_exists($tsPath)) {
                return [file_get_contents($tsPath), '.mjs'];
            }

            // 尝试读取原始文件
            $filePath = app_path($candidate);
            $ext = pathinfo($candidate, PATHINFO_EXTENSION);
            if (file_exists($filePath)) {
                if ($ext === 'php') {
                    throw new ExceptionBusiness('文件类型不支持');
                }
                return [file_get_contents($filePath), $ext, null];
            }
        }

        throw new ExceptionBusiness('文件不存在');
    }

    private function routeToFile(string $path): string
    {
        return $this->normalizePath($path);
    }

    private function normalizePath(string $path): string
    {
        $path = ltrim($path, '/');
        if (preg_match('/[<>:"\\|?*]/', $path)) {
            throw new ExceptionBusiness('路径包含非法字符');
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($parts) {
                    array_pop($parts);
                }
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function buildCandidates(string $path): array
    {
        $candidates = [];
        if ($path !== '') {
            $candidates[] = $path;
        }

        $parts = $path === '' ? [] : explode('/', $path);
        if (count($parts) >= 2) {
            array_splice($parts, 1, 0, ucfirst($this->app));
            $withLayer = implode('/', $parts);
            if ($withLayer && $withLayer !== $path) {
                $candidates[] = $withLayer;
            }
        }

        return array_values(array_unique($candidates));
    }
}
