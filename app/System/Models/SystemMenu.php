<?php

declare(strict_types=1);

namespace App\System\Models;

use App\System\Data\Menu;
use Core\App;
use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Kalnoy\Nestedset\NestedSet;
use Kalnoy\Nestedset\NodeTrait;

#[AutoMigrate]
class SystemMenu extends Model
{

    public $table = "system_menu";

    use NodeTrait;

    public function migration(Blueprint $table)
    {
        $table->id();
        NestedSet::columns($table);
        $table->string('app')->nullable()->comment('应用名称');
        $table->string('label')->nullable()->comment('菜单标签名称');
        $table->string('label_lang')->nullable()->comment('菜单标签语言包键名');
        $table->string('name')->nullable()->comment('菜单路由名称');
        $table->string('path')->nullable()->comment('菜单路由路径');
        $table->string('loader')->nullable()->comment('菜单加载器路径');
        $table->string('icon')->nullable()->comment('菜单图标');
        $table->string('type')->default('menu')->comment('菜单类型 menu-菜单 link-链接');
        $table->string('url')->nullable()->comment('菜单外部链接');
        $table->json('buttons')->nullable()->comment('菜单按钮权限');
        $table->boolean('hidden')->default(false)->nullable()->comment('是否隐藏菜单');
        $table->timestamps();
    }

    protected $casts = [
        'buttons' => 'array',
        'hidden' => 'boolean'
    ];

    protected function getScopeAttributes()
    {
        return ['app'];
    }

    public static function getMenu(string $app): array
    {
        $cache = App::cache()->get('system.menu.' . $app);
        if ($cache) {
            return $cache;
        }
        $menu = self::formatMenu($app);
        App::cache()->set('system.menu.' . $app, $menu);
        return $menu;
    }

    public static function clearMenu(string $app): void
    {
        App::cache()->delete('system.menu.' . $app);
    }


    public static function formatMenu(string $app): array
    {
        $menus = self::scoped(['app' => $app])->with('parent')->defaultOrder()->get();

        $data = [];
        foreach ($menus as $index => $menu) {
            $item = [
                'name' => $menu->name,
                'path' => $menu->path,
                'icon' => $menu->icon,
                'label' => $menu->label,
                'parent' => $menu->parent ? $menu->parent->name : '',
                'hidden' => !!$menu['hidden'],
                'sort' => $index,
                'loader' => $menu->type === 'menu' ? 'remote' : null,
                'meta' => [
                    'path' => $menu->loader,
                    'url' => $menu->url
                ]
            ];

            // 移除空值
            foreach ($item as $key => $value) {
                if ($value === null || $value === '') {
                    unset($item[$key]);
                }
            }
            if (empty($item['meta']['src'])) {
                unset($item['meta']['src']);
            }
            if (empty($item['meta']['url'])) {
                unset($item['meta']['url']);
            }
            if (empty($item['meta'])) {
                unset($item['meta']);
            }

            $data[] = $item;
        }

        return $data;
    }
}
