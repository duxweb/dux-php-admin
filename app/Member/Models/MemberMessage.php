<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class MemberMessage extends \Core\Database\Model
{
    public $table = 'member_message';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'type',
        'content',
        'is_read',
        'created_at',
        'updated_at'
    ];

    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->bigInteger('from_user_id')->comment('发送者ID');
        $table->bigInteger('to_user_id')->comment('接收者ID');
        $table->enum('type', ['text', 'image'])->default('text')->comment('消息类型：text-文字，image-图片');
        $table->text('content')->comment('消息内容');
        $table->boolean('is_read')->default(false)->comment('是否已读');
        $table->timestamps();

        $table->index(['from_user_id', 'to_user_id']);
        $table->index('to_user_id');
        $table->index('created_at');
    }

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(MemberUser::class, 'from_user_id', 'id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(MemberUser::class, 'to_user_id', 'id');
    }

    public function transform(): array
    {
        return [
            'id' => $this->id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'type' => $this->type,
            'content' => $this->content,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}