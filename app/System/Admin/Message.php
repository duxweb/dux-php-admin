<?php

declare(strict_types=1);

namespace App\System\Admin;

use App\System\Handler\MessageHandler;
use App\System\Models\SystemUser;
use Core\Resources\Attribute\Resource;

#[Resource(app: 'admin',  route: '/message', name: 'system.message', actions: ['list'])]
class Message extends MessageHandler
{
    protected string $hasModel = SystemUser::class;
}
