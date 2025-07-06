<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Handler\MenuHandler;
use Core\Resources\Attribute\Resource;

#[Resource(app: 'admin',  route: '/system/menu', name: 'system.menu')]
class Menu extends MenuHandler
{
    protected string $app = 'admin';
}
