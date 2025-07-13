<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Core\Model\TransSet;
use Core\Model\TransTrait;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class MemberNotice extends Model
{
    use TransTrait;

    public $table = 'member_notice';

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->bigInteger('user_id')->nullable()->index();
        $table->string('class_type')->index();
        $table->tinyInteger('type')->comment('类型 0用户 1全部')->index();
        $table->string('image')->comment('封面图')->nullable();
        $table->string('title')->comment('标题');
        $table->string('desc')->comment('描述');
        $table->string('url')->comment('消息链接')->nullable();
        $table->json('data')->comment('数据')->nullable();
        TransSet::columns($table);
        $table->timestamps();
    }

    protected $casts = [
        'data' => 'array',
        'translations' => 'array',
    ];

    public function seed(Connection $db) {}

    public function read(): HasMany
    {
        return $this->hasMany(MemberNoticeRead::class, 'notice_id', 'id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(MemberNoticeClass::class, 'class_type', 'type');
    }
}
