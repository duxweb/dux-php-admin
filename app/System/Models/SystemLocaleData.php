<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;

#[AutoMigrate]
class SystemLocaleData extends \Core\Database\Model
{
    public $table = 'system_locale_data';


    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->string('name')->comment('语言名');
        $table->json('data')->nullable()->comment('数据');
        $table->timestamps();
    }

    protected $casts = [
        'data' => 'array',
    ];
}
