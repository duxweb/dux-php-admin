<?php

declare(strict_types=1);

namespace App\System\Data;

class JsonMenuInterface implements MenuInterface
{
    public function __construct(private array $menuData) {}

    public function getData(): array
    {
        return $this->convertTreeToFlat($this->menuData);
    }

    private function convertTreeToFlat(array $tree, ?string $parent = null): array
    {
        $result = [];

        foreach ($tree as $item) {
            $menuItem = [
                'parent' => $item['parent'] ?: $parent,
                'type' => $item['type'] ?? 'menu',
                'label' => $item['label'],
                'name' => $item['name'],
                'label_lang' => $item['label_lang'] ?? null,
                'path' => $item['path'] ?? null,
                'icon' => $item['icon'] ?? null,
                'loader' => $item['loader'] ?? null,
                'hidden' => $item['hidden'] ?? null,
                'sort' => $item['sort'] ?? null,
            ];

            $result[] = $menuItem;

            if (isset($item['children']) && is_array($item['children'])) {
                $result = array_merge($result, $this->convertTreeToFlat($item['children'], $item['name']));
            }
        }

        return $result;
    }
}