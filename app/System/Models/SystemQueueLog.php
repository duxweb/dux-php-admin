<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class SystemQueueLog extends Model
{
    protected $table = 'system_queue_log';

    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->string('work')->comment('队列名称');
        $table->string('priority')->comment('优先级');
        $table->string('event')->comment('事件');
        $table->string('job_class')->comment('任务类');
        $table->string('job_method')->comment('任务方法');
        $table->string('job_id')->comment('任务ID');
        $table->text('params_json')->comment('参数');
        $table->unsignedInteger('delay_ms')->default(0)->comment('延迟毫秒');
        $table->string('error_message')->nullable()->comment('错误信息');
        $table->string('error_file')->nullable()->comment('错误位置');
        $table->unsignedInteger('duration_ms')->default(0)->comment('执行耗时');
        $table->timestamps();
    }

    public function transform(): array
    {
        return [
            'id' => $this->id,
            'work' => $this->work,
            'priority' => $this->priority,
            'event' => $this->event,
            'job_class' => $this->job_class,
            'job_method' => $this->job_method,
            'job_id' => $this->job_id,
            'params' => (array)json_decode((string)$this->params_json, true),
            'delay_ms' => (int)$this->delay_ms,
            'duration_ms' => (int)$this->duration_ms,
            'error_message' => $this->error_message,
            'error_file' => $this->error_file,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
