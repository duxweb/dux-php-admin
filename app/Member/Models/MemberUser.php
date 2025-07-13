<?php

declare(strict_types=1);

namespace App\Member\Models;

use App\Member\Data\Menu;
use App\Member\Event\UserEvent;
use App\System\Data\Menu as DataMenu;
use Carbon\Carbon;
use Core\App;
use Core\Database\Attribute\AutoMigrate;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[AutoMigrate]
class MemberUser extends \Core\Database\Model
{
    public $table = 'member_user';

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($info) {
            // 清理关联数据
            MemberUnion::query()->where('user_id', $info->id)->delete();
            MemberNotice::query()->where('user_id', $info->id)->delete();
            MemberNoticeRead::query()->where('user_id', $info->id)->delete();
            // NOTE member.delete （用户删除事件）
            App::event()->dispatch(new UserEvent($info), 'member.delete');
        });
    }

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->bigInteger('level_id')->nullable()->comment('等级')->index();
        $table->string('type')->nullable()->comment('用户自定义类型');
        $table->string('nickname')->nullable()->comment('昵称');
        $table->string('email')->nullable()->comment('邮箱');
        $table->string('tel')->nullable()->comment('手机号');
        $table->string('tel_code')->nullable()->comment('手机号区号');
        $table->string('password')->nullable()->comment('密码');
        $table->string('avatar')->nullable()->comment('头像');
        $table->integer('growth')->nullable()->comment('成长值');
        $table->string('birthday')->nullable()->comment('出生日期');
        $table->tinyInteger('sex')->nullable()->comment('性别，0 保密 1 男 2 女');
        $table->string("province", 100)->comment("省份")->nullable();
        $table->string("city", 100)->comment("城市")->nullable();
        $table->string("district", 100)->comment("地区")->nullable();
        $table->string("introduction", 255)->comment("个人介绍")->nullable();
        $table->string('cover')->nullable()->comment('封面图');

        $table->json('info')->nullable()->comment('资料');

        $table->json('setting')->nullable()->comment('设置');
        $table->timestamp('login_at')->nullable()->comment('登录日期');
        $table->boolean('status')->default(true)->comment('状态');
        $table->string('login_ip')->nullable()->comment('登录ip');
        $table->string('login_country')->nullable()->comment('');
        $table->string('login_province')->nullable()->comment('');
        $table->string('login_city')->nullable()->comment('');
        $table->timestamps();
    }

    protected $casts = [
        'setting' => 'array',
        'info' => 'array',
        'login_at' => 'datetime',
    ];

    public function level(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberLevel::class, 'id', 'level_id');
    }

    public function fans(): HasMany
    {
        return $this->hasMany(MemberFans::class, 'user_id', 'id');
    }


    public function getTelFullAttribute(): string
    {
        return $this->tel_code ? $this->tel_code . '-' . $this->tel :  $this->tel;
    }

    public function getSexNameAttribute(): string
    {
        return match ($this->sex) {
            default => '保密',
            1 => '男',
            2 => '女'
        };
    }

    public function getAgeAttribute(): int|string|null
    {
        if (!$this->birthday) {
            return '未知';
        }
        $date = Carbon::parse($this->birthday);
        return (int)$date->diffInYears();
    }

    public function transform(): array
    {
        return [
            "id" => $this->id,
            "level_id" => $this->level_id,
            "level_name" => $this->level->name,
            "nickname" => $this->nickname,
            "email" => $this->email,
            "tel" => $this->tel,
            "tel_code" => $this->tel_code,
            "avatar" => $this->avatar,
            "growth" => $this->growth,
            "sex" => $this->sex,
            "birthday" => $this->birthday,
            "area" => [
                $this->province,
                $this->city,
                $this->district,
            ],
            "introduction" => $this->introduction,
            "cover" => $this->cover,
            "password" => !!$this->password,
            "login_at" => $this->login_at?->toDateTimeString(),
            "login_ip" => $this->login_ip,
            "login_country" => $this->login_country,
            "login_province" => $this->login_province,
            "login_city" => $this->login_city,
            "info" => (object)$this->info,
            "status" => (bool)$this->status,
            "created_at" => $this->created_at?->toDateTimeString(),
        ];
    }

}
