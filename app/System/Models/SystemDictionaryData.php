<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;

#[AutoMigrate]
class SystemDictionaryData extends Model
{

    public $table = "system_dictionary_data";


    public function migration(Blueprint $table)
    {
        $table->id();
        $table->bigInteger('dictionary_id')->nullable()->comment('字典ID');
        $table->string('title')->nullable()->comment('名称');
        $table->string('value')->nullable()->comment('值');
        $table->string('remark')->nullable()->comment('备注');
        $table->timestamps();
    }

    public function dictionary(): BelongsTo
    {
        return $this->belongsTo(SystemDictionary::class, 'dictionary_id');
    }

    public function getFormatValueAttribute()
    {
        switch ($this->dictionary->type) {
            case "number":
                return (float) $this->value;
            case "boolean":
                return (bool) $this->value;
            default:
                return (string) $this->value;
        }
    }
}
