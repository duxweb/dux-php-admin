<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class MemberNoticeCollect extends Model
{
    public $table = 'member_notice_collect';

    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->bigInteger('user_id')->comment('用户id')->index();
        $table->string('has_type')->comment('内容类型')->index();
        $table->bigInteger('has_id')->comment('内容id')->index();
        $table->bigInteger('from_user_id')->comment('来源用户id')->index();
        $table->string('from_has_type')->comment('来源内容类型')->index();
        $table->bigInteger('from_has_id')->comment('来源内容id')->index();
        $table->string('cover')->comment('封面')->nullable();
        $table->string('content')->comment('评论内容')->nullable();
        $table->string('type')->comment('类型 collect:收藏 praise:点赞')->index();
        $table->boolean('read')->default(false)->comment('是否已读');
        $table->timestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(MemberUser::class, 'user_id', 'id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(MemberUser::class, 'from_user_id', 'id');
    }
}
