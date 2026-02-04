<?php
declare(strict_types=1);

namespace App\System\Admin;

use cebe\openapi\Reader;
use Core\Docs\Docs as CoreDocs;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Resource(app: 'admin', route: '/docs', name: 'docs', actions: false)]
class Docs
{
    private function getOpenApi(): object
    {
        $path = data_path('docs/openapi.json');
        if (!file_exists($path) || filesize($path) === 0) {
            $builder = new CoreDocs();
            $builder->build();
        }

        return Reader::readFromJsonFile($path);
    }

    #[Action(methods: 'GET', route: '/catalogs')]
    public function catalogs(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $openapi = $this->getOpenApi();

        $apisByTag = collect($openapi->paths)
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
            ->groupBy('tag');

        $buildTagNode = function ($tagName, $apis) {
            return [
                'id' => 'tag_' . md5($tagName),
                'name' => $tagName,
                'type' => 'group',
                'children' => $apis->map(fn($api) => collect($api)->except('tag')->toArray())->values()->toArray()
            ];
        };

        $tagsByCategory = [];
        foreach (collect($openapi->tags ?? []) as $tag) {
            $tagName = $tag->name ?? '';
            if (!$tagName) {
                continue;
            }
            $category = $tag->{'x-category'} ?? '';
            if (!isset($tagsByCategory[$category])) {
                $tagsByCategory[$category] = [];
            }
            $tagsByCategory[$category][] = $tagName;
        }

        $tagGroups = collect();
        $groupedTags = collect();
        foreach ($tagsByCategory as $categoryName => $tags) {
            $children = collect($tags)
                ->filter(fn($tag) => $apisByTag->has($tag))
                ->map(fn($tag) => $buildTagNode($tag, $apisByTag->get($tag)))
                ->values()
                ->toArray();

            if (empty($children)) {
                continue;
            }

            if ($categoryName) {
                $tagGroups->push([
                    'id' => 'cat_' . md5($categoryName),
                    'name' => $categoryName,
                    'type' => 'category',
                    'children' => $children
                ]);
                $groupedTags = $groupedTags->merge($tags);
            } else {
                $tagGroups = $tagGroups->merge($children);
            }
        }

        $ungrouped = $apisByTag->keys()
            ->filter(fn($tag) => !$groupedTags->contains($tag))
            ->map(fn($tag) => $buildTagNode($tag, $apisByTag->get($tag)))
            ->values();

        $tagGroups = $tagGroups->merge($ungrouped)->values()->toArray();

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
        $openapi = $this->getOpenApi();

        if (str_starts_with($id, 'cat_')) {
            $categories = collect($openapi->tags ?? [])
                ->map(fn($tag) => $tag->{'x-category'} ?? '')
                ->filter()
                ->unique()
                ->values();

            $categoryName = $categories->first(fn($name) => 'cat_' . md5($name) === $id);
            if (!$categoryName) {
                return send($response, "error", ["message" => "Category not found"]);
            }

            return send($response, "ok", [
                'type' => 'category',
                'category' => [
                    'id' => $id,
                    'name' => $categoryName
                ]
            ]);
        }

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

    #[Action(methods: 'POST', route: '/build')]
    public function build(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams() + [
            'host' => null,
            'port' => null,
            'version' => null,
        ];

        $host = $params['host'];
        if (!$host) {
            $host = 'localhost';
        }

        $port = $params['port'];
        if (!$port) {
            $port = '8080';
        }

        $version = $params['version'];
        if ($version === '') {
            $version = null;
        }

        $builder = new CoreDocs();
        $path = $builder->build((string) $host, (string) $port, $version ? (string) $version : null);

        return send($response, "ok", [
            'path' => $path
        ]);
    }
}
