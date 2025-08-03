<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Connection;

#[AutoMigrate]
class SystemNotice extends Model
{
    public $table = "system_notice";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('user_has')->comment('接收用户模型');
        $table->bigInteger('user_id')->comment('接收用户ID');
        $table->string('title')->comment('通知标题');
        $table->text('content')->nullable()->comment('通知内容');
        $table->json('data')->nullable()->comment('附加数据');
        $table->string('url')->nullable()->comment('操作链接');
        $table->boolean('is_read')->default(false)->comment('是否已读');
        $table->timestamp('read_at')->nullable()->comment('阅读时间');
        $table->timestamps();
        
        $table->index(['user_has', 'user_id']);
        $table->index(['is_read']);
    }

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];

    public function seed(Connection $db)
    {
        $db->table($this->table)->insert([
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '账号创建成功',
                'content' => '恭喜您，管理员账号创建成功！请妥善保管您的登录凭证，定期修改密码以确保账户安全。',
                'url' => null,
                'is_read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '系统初始化完成',
                'content' => '系统已完成初始化设置，所有功能模块已就绪。建议您先熟悉系统界面和基本操作流程。',
                'url' => null,
                'is_read' => false,
                'read_at' => null,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '权限配置提醒',
                'content' => '请及时配置用户权限和角色设置，确保系统安全运行。可在权限管理模块进行详细配置。',
                'url' => null,
                'is_read' => true,
                'read_at' => now()->subMinutes(15),
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subMinutes(15),
            ],
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '数据备份建议',
                'content' => '为保障数据安全，建议定期备份重要数据。系统支持自动备份功能，请在系统设置中配置。',
                'url' => 'https://www.dux.cn',
                'is_read' => false,
                'read_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_has' => SystemUser::class,
                'user_id' => 1,
                'title' => '版本更新通知',
                'content' => '系统有新版本可用，包含功能优化和安全补丁。建议及时更新以获得更好的使用体验。',
                'url' => null,
                'is_read' => true,
                'read_at' => now()->subDays(1),
                'created_at' => now()->subDays(1),
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
            'data' => $this->data,
            'url' => $this->url,
            'read' => $this->is_read,
            'desc' => $this->created_at?->format('Y-m-d H:i:s'),
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at?->locale('zh')->diffForHumans(),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function read(): bool
    {
        $this->is_read = true;
        $this->read_at = now();
        return $this->save();
    }

}