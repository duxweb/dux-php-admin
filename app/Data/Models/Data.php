<?php

declare(strict_types=1);

namespace App\Data\Models;

use Core\Database\Attribute\AutoMigrate;
use Kalnoy\Nestedset\NestedSet;
use Kalnoy\Nestedset\NodeTrait;

#[AutoMigrate]
class Data extends \Core\Database\Model
{
    public $table = 'data';

    use NodeTrait;

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        NestedSet::columns($table);
        $table->string('user_type')->comment('关联类型')->index()->nullable();
        $table->string('user_id')->comment('关联ID')->index()->nullable();
        $table->bigInteger('config_id')->comment('配置ID')->index()->nullable();
        $table->json('data')->comment('数据')->nullable();
        $table->timestamps();
    }

    protected $casts = [
        'data' => 'array',
    ];

    protected function getScopeAttributes()
    {
        return ['config_id'];
    }

    public function config(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DataConfig::class, 'id', 'config_id');
    }

    public function transform() {
        $result = [
            "id" => $this->id,
            "parent_id" => $this->parent_id,
            "created_at" => $this->created_at?->format('Y-m-d H:i:s'),
            "updated_at" => $this->updated_at?->format('Y-m-d H:i:s'),
        ];

        if ($this->data && is_array($this->data)) {
            $result = array_merge($result, $this->data);
        }

        return $result;
    }
}
