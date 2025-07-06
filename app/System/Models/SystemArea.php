<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class SystemArea extends \Core\Database\Model
{
    public $table = 'system_area';

    public $timestamps = false;

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->char("parent_code")->default(0);
        $table->char("code")->default(0);
        $table->string("name");
        $table->integer("level");
        $table->boolean("leaf")->default(true);
    }
}
