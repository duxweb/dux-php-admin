<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class SystemSetting extends Model
{

    public $table = "system_setting";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('title')->nullable()->comment('名称');
        $table->string('key')->nullable()->comment('标识');
        $table->text('value')->nullable()->comment('值');
        $table->string('type')->nullable()->comment('类型string,number,json,boolean');
        $table->string('remark')->nullable()->comment('备注');
        $table->boolean('public')->default(false)->comment('公开参数');
        $table->timestamps();
    }

    public function getFormatValueAttribute()
    {
        switch ($this->type) {
            case "number":
                return (float) $this->value;
            case "boolean":
                return (bool) $this->value;
            default:
                return (string) $this->value;
        }
    }

    public function getTypeNameAttribute()
    {
        return match ($this->type) {
            'number' => '数字',
            'boolean' => '布尔',
            'json' => 'JSON',
            default => '字符串',
        };
    }
}
