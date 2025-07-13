<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;

#[AutoMigrate]
class MemberAssess extends \Core\Database\Model
{
    public $table = 'member_assess';

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->bigInteger('user_id')->comment('用户id')->index();
        $table->string('has_type')->comment('关联类型')->index();
        $table->bigInteger('has_id')->comment('关联id')->index();
        $table->string('source_type')->comment('关联来源')->index();
        $table->bigInteger('source_id')->comment('来源id')->index();
        $table->string('image')->comment('关联图片')->nullable();
        $table->string('title')->comment('关联标题')->nullable();
        $table->string('desc')->comment('关联描述')->nullable();
        $table->string('content')->comment('评价内容')->nullable();
        $table->json('images')->comment('评价图片')->nullable();
        $table->float('score')->comment('评分')->nullable();
        $table->boolean('status')->comment('审核状态')->default(true);
        $table->timestamps();
    }

    protected $casts = [
        'images' => 'array',
    ];

    public function has(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'has_type', 'has_id');
    }

    public function source(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberUser::class, 'id', 'user_id');
    }
}
