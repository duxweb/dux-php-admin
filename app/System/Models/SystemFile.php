<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Connection;

#[AutoMigrate]
class SystemFile extends Model
{

    public $table = "system_file";


    public function migration(Blueprint $table)
    {
        $table->id();
        $table->bigInteger('dir_id')->nullable()->comment('目录id');
        $table->string('has_type')->nullable()->comment('关联类型');
        $table->string('driver')->nullable()->comment('驱动类型');
        $table->string('url')->nullable()->comment('链接');
        $table->string('path')->nullable()->comment('路径');
        $table->string('name')->nullable()->comment('文件名');
        $table->string('ext')->nullable()->comment('后缀');
        $table->integer('size')->nullable()->comment('大小');
        $table->string('mime')->nullable()->comment('MIME');
        $table->timestamps();
    }

    public function seed(Connection $db) {}
}
