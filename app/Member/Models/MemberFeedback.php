<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;

#[AutoMigrate]
class MemberFeedback extends \Core\Database\Model
{
    public $table = 'member_feedback';

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->bigInteger('user_id')->comment('用户id')->index();
        $table->string('images')->comment('关联图片')->nullable();
        $table->string('content')->comment('评价内容')->nullable();
        $table->boolean('status')->comment('处理状态')->default(true);
        $table->timestamps();
    }

    protected $casts = [
        'images' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberUser::class, 'id', 'user_id');
    }
}
