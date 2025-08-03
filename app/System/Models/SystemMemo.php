<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[AutoMigrate]
class SystemMemo extends Model
{
    public $table = "system_memo";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('user_has')->comment('用户模型');
        $table->bigInteger('user_id')->comment('用户ID');
        $table->string('title')->comment('备忘录标题');
        $table->text('content')->nullable()->comment('备忘录内容');
        $table->tinyInteger('priority')->default(1)->comment('优先级：1-低 2-中 3-高');
        $table->boolean('is_completed')->default(false)->comment('是否完成');
        $table->timestamp('remind_at')->nullable()->comment('提醒时间');
        $table->timestamp('completed_at')->nullable()->comment('完成时间');
        $table->timestamps();
    }

    protected $casts = [
        'remind_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function seed(\Illuminate\Database\Connection $db)
    {
        $db->table($this->table)->insert([
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '完善个人资料',
                'content' => '请及时完善您的个人资料信息，包括头像、邮箱、手机号等，以便系统更好地为您服务。',
                'priority' => 2, // 中等优先级
                'is_completed' => false,
                'remind_at' => now()->addDays(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '系统安全检查',
                'content' => '定期检查系统安全设置，确保数据安全。建议每月进行一次全面的安全检查。',
                'priority' => 3, // 高优先级
                'is_completed' => false,
                'remind_at' => now()->addWeek(),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '熟悉系统功能',
                'content' => '花时间了解系统各个功能模块，提高工作效率。可以查看帮助文档或参加培训。',
                'priority' => 1, // 低优先级
                'is_completed' => true,
                'remind_at' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(1),
            ]
        ]);
    }

    public function user(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'user_has', 'user_id');
    }

    public function transform(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority,
            'priority_text' => $this->priority_text,
            'is_completed' => $this->is_completed,
            'remind_at' => $this->remind_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function getPriorityTextAttribute(): string
    {
        return match($this->priority) {
            1 => '低',
            2 => '中',
            3 => '高',
            default => '低'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_completed ? '已完成' : '未完成';
    }
}