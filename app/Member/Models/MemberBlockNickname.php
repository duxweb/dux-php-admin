<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Model\TransSet;

#[AutoMigrate]
class MemberBlockNickname extends \Core\Database\Model
{

    public $table = 'member_block_nickname';

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        $table->string('nickname')->nullable()->comment('名称');
        $table->timestamps();
    }
}
