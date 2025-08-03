<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[AutoMigrate]
class SystemBulletinRead extends Model
{
    public $table = "system_bulletin_read";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->bigInteger('bulletin_id')->comment('公告ID');
        $table->string('user_has')->comment('用户模型');
        $table->bigInteger('user_id')->comment('用户ID');
        $table->timestamps();
        
        $table->unique(['bulletin_id', 'user_has', 'user_id'], 'unique_bulletin_user');
        $table->index(['bulletin_id']);
        $table->index(['user_has', 'user_id']);
    }

    public function user(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'user_has', 'user_id');
    }

    public function bulletin()
    {
        return $this->belongsTo(SystemBulletin::class, 'bulletin_id');
    }
}