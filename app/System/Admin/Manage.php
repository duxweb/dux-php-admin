<?php

namespace App\System\Admin;

use App\System\Handler\ManageHandler;
use Core\Resources\Attribute\Resource;

#[Resource(app: 'admin', route: '', name: 'system.manage', actions: false)]
class Manage extends ManageHandler
{
    protected string $app = 'admin';
}
