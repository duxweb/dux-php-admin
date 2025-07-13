<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class MemberUnion extends Model
{
    public $table = 'member_union';
    protected $casts = [
        'data' => 'array'
    ];

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->bigInteger('user_id')->comment('用户id')->index();
        $table->string('type')->comment('关联类型')->index();
        $table->string('app_id')->comment('关联应用id')->nullable()->index();
        $table->string('open_id')->comment('关联id')->nullable()->index();
        $table->string('union_id')->comment('联合id')->nullable()->index();
        $table->json('data')->comment('登录数据')->nullable();
        $table->timestamps();
    }
}
