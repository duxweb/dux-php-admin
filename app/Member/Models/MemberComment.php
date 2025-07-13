<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NestedSet;
use Kalnoy\Nestedset\NodeTrait;

#[AutoMigrate]
class MemberComment extends \Core\Database\Model
{
    public $table = 'member_comment';

    use NodeTrait;

    public function migration(\Illuminate\Database\Schema\Blueprint $table): void
    {
        $table->id();
        $table->bigInteger('user_id')->comment('用户id');
        $table->string('has_type')->comment('关联类型')->index();
        $table->bigInteger('has_id')->comment('关联id')->index();
        $table->string('image')->comment('评论图片')->nullable();
        $table->string('content')->comment('评论内容');
        $table->boolean('status')->comment('状态')->default(0);
        $table->bigInteger('praise')->comment('点赞')->default(0);
        $table->tinyInteger('comment')->comment('评论')->default(0);
        $table->string('ip')->comment('评论IP')->nullable();
        $table->string('country')->comment('评论国家')->nullable();
        $table->string('province')->comment('评论省份')->nullable();
        $table->string('city')->comment('评论城市')->nullable();
        NestedSet::columns($table);
        $table->timestamps();
    }

    protected function getScopeAttributes()
    {
        return ['has_type', 'has_id'];
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function($model) {
            MemberComment::query()
                ->where('has_type', MemberComment::class)
                ->where('has_id', $model->id)
                ->delete();
        });
    }

    public function hastable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'has_type', 'has_id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberUser::class, 'id', 'user_id');
    }

    public function praises(): HasMany
    {
        return $this->hasMany(
            MemberPraise::class,
            'has_id',
            'id'
        )->where('has_type', self::class);
    }
}