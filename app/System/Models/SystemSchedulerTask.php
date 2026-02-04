<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class SystemSchedulerTask extends Model
{
    protected $table = 'system_scheduler_task';

    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->string('selected_task')->comment('任务索引');
        $table->string('name')->comment('任务名称');
        $table->string('cron')->comment('Cron 表达式');
        $table->string('desc')->nullable()->comment('描述');
        $table->unsignedInteger('sort')->default(0)->comment('排序');
        $table->unsignedTinyInteger('status')->default(1)->comment('状态');
        $table->timestamps();
    }

    public function transform(): array
    {
        return [
            'id' => $this->id,
            'selected_task' => $this->selected_task,
            'name' => $this->name,
            'cron' => $this->cron,
            'desc' => $this->desc,
            'sort' => $this->sort,
            'status' => (bool)$this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
