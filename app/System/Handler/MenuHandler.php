<?php

declare(strict_types=1);

namespace App\System\Handler;

use App\System\Models\SystemMenu;
use Core\App;
use Core\Model\Nestedset;
use Core\Resources\Action\Resources;
use Core\Resources\Attribute\Action;
use Core\Validator\Data;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MenuHandler extends Resources
{
    protected string $app = 'admin';

    protected string $model = SystemMenu::class;

    protected bool $tree = true;
    protected array $pagination = [
        'status' => false,
    ];

    public function queryMany(Builder $query, ServerRequestInterface $request, array $args): void
    {
        $params = $request->getQueryParams();
        $query->defaultOrder()->where('label', 'like', '%' . $params['label'] . '%');
    }

    public function queryModel(string $model): Builder
    {
        return SystemMenu::scoped([ 'app' => $this->app ]);
    }

    public function transform(object $item): array
    {
        return [
            "id" => $item->id,
            "label" => $item->label,
            "label_lang" => $item->label_lang,
            "name" => $item->name,
            "path" => $item->path,
            "parent_id" => $item->parent_id,
            "loader" => $item->loader,
            "icon" => $item->icon ?: null,
            "type" => $item->type,
            "url" => $item->url,
            "buttons" => $item->buttons,
            "hidden" => !!$item->hidden,
            "children" => $item->children ? $item->children->map(function ($vo) {
                return $this->transform($vo);
            }) : []
        ];
    }

    public function validator(array $data, ServerRequestInterface $request, array $args): array
    {
        return [
            "name" => ["required", '请输入菜单标识'],
            "label" => ["required", '请输入菜单名称'],
        ];
    }

    public function format(Data $data, ServerRequestInterface $request, array $args): array
    {

        return [
            'app' => $this->app,
            'label' => fn () => $data->label,
            'label_lang' => fn () => $data->label_lang,
            'name' => fn () => $data->name,
            'path' => $data->path,
            'parent_id' => $data->parent_id,
            'loader' => $data->loader,
            'icon' => $data->icon,
            'type' => $data->type,
            'url' => $data->url,
            'buttons' => $data->buttons,
            'hidden' => !!$data->hidden,
        ];
    }

    public function createBefore(Data $data, $model): void
    {
        if (SystemMenu::query()->where('name', $data->name)->exists()) {
            throw new \Exception('菜单标识已存在');
        }
    }

    public function createAfter(Data $data, $model): void
    {
        SystemMenu::clearMenu($this->app);
    }

    public function editAfter(Data $data, $model): void
    {
        SystemMenu::clearMenu($this->app);
    }

    public function delAfter($model): void
    {
        SystemMenu::clearMenu($this->app);
    }

    #[Action(methods: 'GET', route: '/button')]
    public function button(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (!$params['name']) {
            throw new \Exception('请输入菜单标识');
        }

        $data = App::permission()->get($this->app);

        $permissions = $data->get();

        $node = [];
        foreach ($permissions as $parentNode) {
            if (!$parentNode['children']) {
                continue;
            }
            foreach ($parentNode['children'] as $child) {
                if ($child['name'] !== $params['name']) {
                    continue;
                }
                $node = $parentNode['children'];
            }
        }

        return send($response, "ok", $node);
    }

    #[Action(methods: 'POST', route: '/sort')]
    public function sort(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody();

        $id = (int)$params['id'];
        $beforeId = (int)$params['before_id'];
        $parentId = (int)$params['parent_id'];

        Nestedset::sort(SystemMenu::scoped([ 'app' => $this->app ]), $id, $beforeId, $parentId);

        SystemMenu::clearMenu($this->app);
        return send($response, "ok");
    }
}
