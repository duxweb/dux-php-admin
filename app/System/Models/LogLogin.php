<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class LogLogin extends Model
{

    public $table = "log_login";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('user_type')->comment("关联类型");
        $table->string('user_id')->comment("关联id");
        $table->string('browser')->nullable();
        $table->string('ip')->nullable();
        $table->string('platform')->nullable();
        $table->boolean('status')->default(true);
        $table->timestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(SystemUser::class, 'user_id', 'id');
    }
}
