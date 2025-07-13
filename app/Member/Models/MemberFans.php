<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class MemberFans extends Model
{
    public $table = 'member_fans';

    public function migration(Blueprint $table): void
    {
        $table->id();
        $table->bigInteger('user_id')->comment('用户id')->index();
        $table->bigInteger('fans_user_id')->comment('用户id')->index();
        $table->timestamps();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberUser::class, 'id', 'user_id');
    }

    public function fans(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberUser::class, 'id', 'fans_user_id');
    }

}