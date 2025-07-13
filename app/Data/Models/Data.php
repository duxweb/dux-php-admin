<?php

declare(strict_types=1);

namespace App\Data\Models;

use Core\Database\Attribute\AutoMigrate;
use Kalnoy\Nestedset\NestedSet;
use Kalnoy\Nestedset\NodeTrait;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

#[AutoMigrate]
class Data extends \Core\Database\Model
{
    public $table = 'data';

    use NodeTrait;
    use HasJsonRelationships;

    public function migration(\Illuminate\Database\Schema\Blueprint $table)
    {
        $table->id();
        NestedSet::columns($table);
        $table->string('has_type')->comment('关联类型')->index()->nullable();
        $table->string('has_id')->comment('关联ID')->index()->nullable();
        $table->bigInteger('config_id')->comment('配置ID')->index()->nullable();
        $table->json('data')->comment('数据')->nullable();
        $table->timestamps();
    }

    protected $casts = [
        'data' => 'array',
    ];

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

        // 处理数据字段
        if ($this->data && is_array($this->data)) {
            if ($this->config->field_data && isset($this->config->field_data['data'])) {
                // 根据配置格式化字段
                foreach ($this->config->field_data['data'] as $fieldConfig) {
                    $field = $fieldConfig['field'];
                    $type = $fieldConfig['type'];
                    $defaultValue = $fieldConfig['default_value'] ?? null;
                    $value = $this->data[$field] ?? $defaultValue;
                    
                    $result[$field] = $this->formatFieldValue($value, $type);
                }
            } else {
                // 如果没有配置，直接合并数据
                $result = array_merge($result, $this->data);
            }
        }

        // 处理关联数据
        if ($this->config->field_data && isset($this->config->field_data['has'])) {
            foreach ($this->config->field_data['has'] as $relation) {
                if (isset($relation['name']) && isset($relation['fields'])) {
                    $relationName = $relation['name'];
                    $fields = $relation['fields'];
                    
                    if ($this->relationLoaded($relationName) && $this->{$relationName}) {
                        $relationData = $this->{$relationName};
                        
                        if ($relation['type'] === 'hasMany') {
                            // 一对多关系，处理集合
                            $result[$relationName] = $relationData->map(function ($item) use ($fields) {
                                $itemData = [];
                                foreach ($fields as $field) {
                                    $itemData[$field] = $item->{$field} ?? null;
                                }
                                return $itemData;
                            })->toArray();
                        } else {
                            // 一对一或反向关联，处理单个对象
                            $itemData = [];
                            foreach ($fields as $field) {
                                $itemData[$field] = $relationData->{$field} ?? null;
                            }
                            $result[$relationName] = $itemData;
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function formatFieldValue($value, string $type) {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'int':
                return (int) $value;
            case 'decimal':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'datetime':
                return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
            case 'date':
                return $value ? date('Y-m-d', strtotime($value)) : null;
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'string':
            default:
                return (string) $value;
        }
    }
}
