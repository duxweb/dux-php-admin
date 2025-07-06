<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class SystemMessage extends Model
{

    public $table = "system_message";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('user_has')->nullable()->comment('用户模型');
        $table->bigInteger('user_id')->nullable()->comment('用户ID');

        $table->string('send_user_has')->nullable()->comment('发送用户模型');
        $table->bigInteger('send_user_id')->nullable()->comment('发送用户ID');

        $table->string('sender_dept_has')->nullable()->comment('发送部门模型');
        $table->bigInteger('sender_dept_id')->nullable()->comment('发送部门ID');

        $table->string('sender')->nullable()->comment('发送人');
        $table->string('sender_dept')->nullable()->comment('发送部门');

        $table->string('icon')->nullable()->comment('图标');
        $table->string('title')->nullable()->comment('标题');
        $table->string('desc')->nullable()->comment('描述');
        $table->text('content')->nullable()->comment('内容');
        $table->boolean('read')->default(false)->comment('是否已读');
        $table->timestamp('read_at')->nullable()->comment('阅读时间');
        $table->timestamps();
    }

    protected $casts = [
        'read_at' => 'datetime'
    ];

    public function user(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'user_has', 'user_id');
    }

    public function sendUser(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'send_user_has', 'send_user_id');
    }

    public function senderDept(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'sender_dept_has', 'sender_dept_id');
    }
}
