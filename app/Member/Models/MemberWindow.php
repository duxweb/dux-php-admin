<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class MemberWindow extends Model
{
    public $table = 'member_window';

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->bigInteger('user_id')->nullable()->index();
        $table->string('type', 20)->comment('自定义类型')->index();
        $table->string('title')->comment('标题');
        $table->string('desc')->comment('描述');
        $table->json('data')->comment('附加数据')->nullable();
        $table->string('url')->comment('消息链接')->nullable();
        $table->boolean('status')->comment('状态')->default(true);
        $table->timestamps();
    }

    protected $casts = [
      'data' => 'array'
    ];

    public function seed(Connection $db)
    {
    }

}
