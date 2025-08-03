<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Connection;

#[AutoMigrate]
class SystemBulletin extends Model
{
    public $table = "system_bulletin";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('title')->comment('公告标题');
        $table->text('content')->comment('公告内容');
        $table->tinyInteger('status')->default(1)->comment('状态：0-下线 1-发布');
        $table->tinyInteger('type')->default(1)->comment('类型：1-通知 2-公告 3-活动');
        $table->tinyInteger('target_type')->default(1)->comment('发布目标：1-全部 2-部门 3-角色');
        $table->json('target_departments')->nullable()->comment('目标部门ID数组');
        $table->json('target_roles')->nullable()->comment('目标角色ID数组');
        $table->boolean('is_top')->default(false)->comment('是否置顶');
        $table->integer('sort')->default(0)->comment('排序');
        $table->bigInteger('user_id')->nullable()->comment('创建用户ID');
        $table->timestamp('publish_at')->nullable()->comment('发布时间');
        $table->timestamp('expire_at')->nullable()->comment('过期时间');
        $table->timestamps();
    }

    protected $casts = [
        'target_departments' => 'array',
        'target_roles' => 'array',
        'publish_at' => 'datetime',
        'expire_at' => 'datetime'
    ];

    public function seed(Connection $db)
    {
        $db->table($this->table)->insert([
            [
                'user_id' => 1,
                'title' => '系统部署成功',
                'content' => '恭喜您成功部署了 Dux 管理系统！系统已完成初始化配置，您现在可以开始使用各项功能。如有问题请查看官方文档或联系技术支持。',
                'status' => 1,
                'type' => 2, // 公告类型
                'target_type' => 1, // 全部用户
                'publish_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'title' => '欢迎使用 Dux',
                'content' => '感谢您选择 Dux 管理系统。我们致力于为您提供高效、稳定的后台管理解决方案。请定期关注系统更新，获取最新功能和安全补丁。',
                'status' => 1,
                'type' => 1, // 通知类型
                'target_type' => 1, // 全部用户
                'publish_at' => now()->subDays(1),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => 1,
                'title' => '安全提醒',
                'content' => '为了您的账户安全，建议您：1. 定期修改密码；2. 不要在公共场所登录；3. 及时退出登录。如发现异常登录请立即联系管理员。',
                'status' => 1,
                'type' => 1, // 通知类型
                'target_type' => 1, // 全部用户
                'publish_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(SystemUser::class, 'user_id');
    }

    public function depts(): BelongsToMany
    {
        return $this->belongsToMany(SystemDept::class, 'system_bulletin_dept', 'bulletin_id', 'dept_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SystemRole::class, 'system_bulletin_role', 'bulletin_id', 'role_id');
    }

    public function readRecords()
    {
        return $this->hasMany(SystemBulletinRead::class, 'bulletin_id');
    }

    public function readUsers()
    {
        return $this->hasManyThrough(
            SystemUser::class,
            SystemBulletinRead::class,
            'bulletin_id',
            'id', 
            'id',
            'user_id'
        )->where('system_bulletin_read.user_has', SystemUser::class);
    }

    public function transform(?int $userId = null): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'target_type' => $this->target_type,
            'target_departments' => $this->target_departments,
            'target_roles' => $this->target_roles,
            'is_top' => (bool)$this->is_top,
            'status' => (bool)$this->status,
            'sort' => $this->sort,
            'user_id' => $this->user_id,
            'username' => $this->user?->username,
            'read_users' => $this->readUsers?->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                ];
            })->toArray() ?? [],
            'read_count' => $this->readUsers?->count() ?? 0,
            'read' => $userId ? $this->readUsers?->contains('id', $userId) ?? false : false,
            'publish_at' => $this->publish_at?->format('Y-m-d H:i:s'),
            'expire_at' => $this->expire_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function getTargetTypeTextAttribute(): string
    {
        return match($this->target_type) {
            1 => '全部用户',
            2 => '指定部门',
            3 => '指定角色',
            default => '未知'
        };
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            1 => '通知',
            2 => '公告', 
            3 => '活动',
            default => '通知'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            0 => '下线',
            1 => '发布',
            default => '下线'
        };
    }
}