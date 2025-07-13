<?php

namespace App\System\Service;

use App\System\Data\MenuInterface;
use App\System\Models\SystemMenu;
use Illuminate\Database\Connection;

class Menu
{
    public static function install(Connection $db, MenuInterface $menu, string $app, ?int $lastId = null): array
    {
        $lastId = $lastId ?: $db->table('system_menu')->max('id');
        $menuData = $menu->getData();
        $menuData = self::assignMultipleMenuIds($app, $menuData, $lastId + 1);

        $existingMenus = $db->table('system_menu')->where('app', $app)->pluck('name')->toArray();

        $newMenus = array_filter($menuData, function ($menu) use ($existingMenus) {
            return !in_array($menu['name'], $existingMenus);
        });

        if (!empty($newMenus)) {
            usort($newMenus, function ($a, $b) {
                return $a['id'] <=> $b['id'];
            });
            $db->table('system_menu')->insert($newMenus);
        }

        SystemMenu::scoped(['app' => $app])->fixTree();
        SystemMenu::clearMenu($app);

        return $menuData;
    }

    public static function assignMultipleMenuIds(string $app, array $menuGroups, int $startId = 1): array
    {
        $currentId = $startId;
        $allItems = [];

        $parentIdMap = SystemMenu::query()
            ->where('app', $app)
            ->pluck('id', 'name')
            ->toArray();

        // 收集所有菜单项
        foreach ($menuGroups as $group) {
            if (is_array($group)) {
                if (self::isMenuItem($group)) {
                    $allItems[] = $group;
                } else {
                    foreach ($group as $item) {
                        if (is_array($item) && self::isMenuItem($item)) {
                            $allItems[] = $item;
                        }
                    }
                }
            }
        }

        // 按sort字段排序
        usort($allItems, function ($a, $b) {
            $sortA = $a['sort'] ?? null;
            $sortB = $b['sort'] ?? null;

            if ($sortA !== null && $sortB !== null) {
                return $sortA <=> $sortB;
            }

            return ($sortA !== null) ? -1 : (($sortB !== null) ? 1 : 0);
        });

        // 分配ID和嵌套集字段
        $sortOrder = 1;
        foreach ($allItems as &$item) {
            if (isset($parentIdMap[$item['name']])) {
                // 已存在菜单保持原ID
                $item['id'] = $parentIdMap[$item['name']];
            } else {
                // 新菜单分配新ID
                $item['id'] = $currentId;
                $parentIdMap[$item['name']] = $currentId;
                $currentId++;
            }
            $item['_lft'] = $sortOrder++;
            $item['_rgt'] = 0;
            unset($item['sort']);
        }

        // 处理父级关系
        foreach ($allItems as &$item) {
            if (empty($item['parent'])) {
                $item['parent_id'] = null;
            } elseif (isset($parentIdMap[$item['parent']])) {
                $item['parent_id'] = $parentIdMap[$item['parent']];
            } else {
                // 创建孤立菜单容器（如果不存在）
                if (!isset($parentIdMap['orphaned'])) {
                    $orphanedMenu = [
                        'id' => $currentId,
                        'name' => 'orphaned',
                        'label' => '孤立菜单',
                        'label_lang' => 'orphaned',
                        'path' => null,
                        'icon' => 'i-tabler:unlink',
                        'loader' => null,
                        'type' => 'directory',
                        'parent_id' => null,
                        'app' => $app,
                        'url' => null,
                        'buttons' => null,
                        'hidden' => 0,
                        '_lft' => $sortOrder++,
                        '_rgt' => 0,
                    ];
                    $allItems[] = $orphanedMenu;
                    $parentIdMap['orphaned'] = $currentId++;
                }
                $item['parent_id'] = $parentIdMap['orphaned'];
            }

            // 统一设置通用字段
            unset($item['parent']);
            $item['app'] = $app;
            $item['url'] = $item['url'] ?? null;
            $item['buttons'] = $item['buttons'] ?? null;
            $item['hidden'] = $item['hidden'] ?? 0;
        }

        return $allItems;
    }

    private static function isMenuItem(array $item): bool
    {
        return isset($item['name']) && isset($item['label']) && isset($item['type']);
    }
}
