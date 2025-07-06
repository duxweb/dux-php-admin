<?php

namespace App\System\Admin;

use cebe\openapi\Reader;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/docs', name: 'docs', actions: false)]
class Docs
{
    #[Action(methods: 'GET', route: '/catalogs')]
    public function catalogs(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $openapi = Reader::readFromJsonFile(data_path('docs/openapi.json'));

        // 使用 collect 构建树形目录结构
        $tagGroups = collect($openapi->paths)
            ->flatMap(function ($pathItem, $path) {
                return collect(['get', 'post', 'put', 'delete', 'patch', 'head', 'options'])
                    ->filter(fn($method) => isset($pathItem->{$method}))
                    ->map(function ($method) use ($pathItem, $path) {
                        $operation = $pathItem->{$method};
                        $tags = $operation->tags ?? ['默认'];
                        $operationId = $operation->operationId ?? $method . '_' . str_replace(['/', '{', '}'], ['_', '', ''], $path);

                        return collect($tags)->map(fn($tag) => [
                            'tag' => $tag,
                            'id' => $operationId,
                            'name' => $operation->summary ?? $operationId,
                            'method' => strtoupper($method),
                            'path' => $path,
                            'deprecated' => $operation->deprecated ?? false
                        ]);
                    })
                    ->flatten(1);
            })
            ->groupBy('tag')
            ->map(function ($apis, $tagName) {
                return [
                    'id' => 'tag_' . md5($tagName),
                    'name' => $tagName,
                    'type' => 'group',
                    'children' => $apis->map(fn($api) => collect($api)->except('tag')->toArray())->values()->toArray()
                ];
            })
            ->values()
            ->toArray();

        return send($response, "ok", $tagGroups, [
            'info' => [
                'title' => $openapi->info->title ?? 'API Documentation',
                'version' => $openapi->info->version ?? '1.0.0',
                'description' => $openapi->info->description ?? ''
            ]
        ]);
    }

    #[Action(methods: 'GET', route: '/info/{id}')]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $openapi = Reader::readFromJsonFile(data_path('docs/openapi.json'));

        // 检查是否是标签分组ID
        if (str_starts_with($id, 'tag_')) {
            // 通过MD5反查找标签名
            $tagInfo = collect($openapi->tags)
                ->first(function ($tag) use ($id) {
                    return 'tag_' . md5($tag->name) === $id;
                });

            if (!$tagInfo) {
                return send($response, "error", ["message" => "Tag not found"]);
            }

            return send($response, "ok", [
                'type' => 'tag',
                'tag' => [
                    'id' => $id,
                    'name' => $tagInfo->name,
                    'description' => $tagInfo->description,
                ]
            ]);
        }

        // 查找单个API操作，直接返回原始数据
        $apiInfo = collect($openapi->paths)
            ->flatMap(function ($pathItems, $path) {
                $data = [];
                foreach ($pathItems->getOperations() as $method => $operation) {
                    $data[] = [
                        'method' => $method,
                        'path' => $path,
                        ...$operation->getRawSpecData(),
                    ];
                }
                return $data;
            })
            ->first(fn($api) => $api['operationId'] === $id);

        if (!$apiInfo) {
            return send($response, "error", ["message" => "API not found"]);
        }

        return send($response, "ok", [
            'type' => 'api',
            'api' => $apiInfo
        ]);
    }
}
