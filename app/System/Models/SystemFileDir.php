<?php

declare(strict_types=1);

namespace App\System\Models;

use Core\Database\Attribute\AutoMigrate;
use Core\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Connection;
use Kalnoy\Nestedset\NestedSet;
use Kalnoy\Nestedset\NodeTrait;

#[AutoMigrate]
class SystemFileDir extends Model
{

    public $table = "system_file_dir";

    use NodeTrait;

    public function migration(Blueprint $table)
    {
        $table->id();
        NestedSet::columns($table);
        $table->string('name')->nullable()->comment('名称');
        $table->string('has_type')->nullable()->comment('关联类型');
        $table->timestamps();
    }

    public function seed(Connection $db) {}
}
