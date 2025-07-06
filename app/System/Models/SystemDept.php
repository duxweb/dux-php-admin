<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NestedSet;
use Kalnoy\Nestedset\NodeTrait;

#[AutoMigrate]
class SystemDept extends Model
{

    public $table = "system_dept";

    use NodeTrait;

    public function migration(Blueprint $table)
    {
        $table->id();
        NestedSet::columns($table);
        $table->string('name')->nullable()->comment('部门名称');
        $table->timestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(SystemUser::class, 'dept_id');
    }
}
