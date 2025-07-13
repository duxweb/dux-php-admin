<?php

declare(strict_types=1);

namespace App\System\Data;

class CombinedMenuInterface implements MenuInterface
{
    public function __construct(private array $allMenus) {}

    public function getData(): array
    {
        return $this->allMenus;
    }
}
