<?php

declare(strict_types=1);

namespace App\Member\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Core\Model\TransSet;
use Core\Model\TransTrait;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class MemberNoticeClass extends Model
{
    use TransTrait;

    public $table = 'member_notice_class';

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('type')->comment('类型');
        $table->string('name')->comment('名称');
        TransSet::columns($table);
        $table->timestamps();
    }

    public function seed(Connection $db) {}

    public function notices(): HasMany
    {
        return $this->hasMany(MemberNotice::class, 'class_type', 'type');
    }
}
