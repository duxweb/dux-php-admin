<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[AutoMigrate]
class SystemDictionary extends Model
{

    public $table = "system_dictionary";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('title')->nullable()->comment('名称');
        $table->string('key')->nullable()->comment('标识');
        $table->string('remark')->nullable()->comment('备注');
        $table->string('type')->nullable()->comment('类型string,number,boolean');
        $table->timestamps();
    }

    public function data(): HasMany
    {
        return $this->hasMany(SystemDictionaryData::class, 'dictionary_id');
    }

    public function getTypeNameAttribute()
    {
        return match ($this->type) {
            'number' => '数字',
            'boolean' => '布尔',
            default => '字符串',
        };
    }
}
