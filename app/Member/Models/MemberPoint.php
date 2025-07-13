<?php

declare(strict_types=1);

namespace App\Member\Models;

use App\Member\Event\UserEvent;
use Carbon\Carbon;
use Core\App;
use Core\Database\Attribute\AutoMigrate;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberPoint extends MemberUser
{
}
