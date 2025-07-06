<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;

#[AutoMigrate]
class SystemLocale extends \Core\Database\Model
{
    public $table = 'system_locale';


    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->string('title')->comment('语言');
        $table->string('name')->comment('语言名');
        $table->timestamps();
    }
}
