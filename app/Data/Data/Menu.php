<?php

declare(strict_types=1);

namespace App\Data\Data;

use App\System\Data\MenuInterface;
use Core\Database\Attribute\AutoMigrate;

#[AutoMigrate]
class Menu implements MenuInterface
{

    public function getData(): array
    {
        return [
            $this->data(),
        ];
    }

    private function data(): array
    {
        return [
            [
                'parent' => null,
                'type' => 'menu',
                'label' => '数据集',
                'name' => 'data.config.list',
                'label_lang' => 'data.config.list',
                'path' => 'data/config',
                'icon' => 'i-tabler:database',
                'loader' => 'Data/Config/table',
                'hidden' => null,
                'sort' => 800,
            ],
        ];
    }

}
