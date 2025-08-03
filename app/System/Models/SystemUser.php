<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Connection;

#[AutoMigrate]
class SystemUser extends Model
{

    public $table = "system_user";

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->bigInteger('role_id')->nullable();
        $table->bigInteger('dept_id')->nullable();
        $table->string('username')->unique();
        $table->string('nickname');
        $table->string('password');
        $table->string('avatar')->nullable();
        $table->string('tel')->nullable();
        $table->string('email')->nullable();
        $table->string('lang')->nullable();
        $table->boolean('status')->default(true);
        $table->timestamps();
    }

    public function seed(Connection $db)
    {
        $db->table($this->table)->insert([
            'role_id' => 1,
            'username' => 'admin',
            'nickname' => 'Admin',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function operates(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(LogOperate::class, 'user');
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SystemRole::class, 'role_id');
    }

    public function dept(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SystemDept::class, 'dept_id');
    }

    public function getPermissionAttribute(): array
    {
        $data = [];
        if (!$this->role->permission) {
            return $data;
        }
        return $this->role->permission;
    }
}
