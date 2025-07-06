<?php

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use \Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[AutoMigrate]
class SystemRole extends Model
{

    public $table = "system_role";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('name')->comment('角色名称');
        $table->string('desc')->nullable()->comment('描述');
        $table->tinyInteger('data_type')->default(0)->comment('数据权限类型');
        $table->json('permission')->nullable();
        $table->json('data_permission')->nullable();
        $table->timestamps();
    }

    protected $casts = [
        'permission' => 'array'
    ];

    public function seed(Connection $db)
    {
        $db->table($this->table)->insert([
            'name' => 'Admins',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function users(): HasMany
    {
        return $this->hasMany(SystemUser::class, 'role_id');
    }
}
