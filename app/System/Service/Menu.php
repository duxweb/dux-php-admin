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

        $newMenus = array_filter($menuData, function($menu) use ($existingMenus) {
            return !in_array($menu['name'], $existingMenus);
        });

        if (!empty($newMenus)) {
            usort($newMenus, function($a, $b) {
                return $a['id'] <=> $b['id'];
            });
            $db->table('system_menu')->insert($newMenus);
        }

        SystemMenu::scoped(['app' => $app])->fixTree();

        return $menuData;
    }

    public static function assignMultipleMenuIds(string $app, array $menuGroups, int $startId = 1): array
    {
        $currentId = $startId;
        $allItems = [];
        $parentIdMap = [];

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

        usort($allItems, function($a, $b) {
            $sortA = $a['sort'] ?? null;
            $sortB = $b['sort'] ?? null;

            if ($sortA !== null && $sortB !== null) {
                return $sortA <=> $sortB;
            }

            if ($sortA !== null && $sortB === null) {
                return -1;
            }
            if ($sortA === null && $sortB !== null) {
                return 1;
            }

            return 0;
        });

        $sortOrder = 1;
        foreach ($allItems as &$item) {
            $item['id'] = $currentId;
            $item['_lft'] = $sortOrder;
            $item['_rgt'] = 0;
            $parentIdMap[$item['name']] = $currentId;
            $currentId++;
            $sortOrder++;

            unset($item['sort']);
        }

        foreach ($allItems as &$item) {
            if (!isset($item['parent']) || $item['parent'] === null) {
                $item['parent_id'] = null;
            } elseif (is_string($item['parent']) && isset($parentIdMap[$item['parent']])) {
                $item['parent_id'] = $parentIdMap[$item['parent']];
            } else {
                if (!isset($parentIdMap['orphaned'])) {
                    $allItems[] = [
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
                        '_lft' => $sortOrder,
                        '_rgt' => 0,
                    ];
                    $parentIdMap['orphaned'] = $currentId;
                    $currentId++;
                    $sortOrder++;
                }
                $item['parent_id'] = $parentIdMap['orphaned'];
            }

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
