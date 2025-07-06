<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class SystemStorage extends Model
{

    public $table = "system_storage";


    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('title')->nullable()->comment('配置名称');
        $table->string('name')->nullable()->comment('配置名称');
        $table->string('type')->nullable()->comment('类型local,s3');
        $table->json('config')->nullable()->comment('配置');
        $table->timestamps();
    }

    public $casts = [
        'config' => 'array',
    ];

    public function getTypeNameAttribute()
    {
        return match ($this->type) {
            'local' => '本地存储',
            's3' => 'S3存储',
            default => '未知',
        };
    }
}
