<?php

declare(strict_types=1);

namespace App\Data\Service;

use App\Data\Models\Data as ModelsData;
use App\Data\Models\DataConfig;
use Core\Validator\Data;

class Config
{

    public static function has($query, $config) {
        if ($config->field_data['has']) {
            foreach ($config->field_data['has'] as $relation) {
                if ($relation['name'] && $relation['model']) {
                    $relationName = $relation['name'];
                    $modelClass = $relation['model'];
                    $localKey = $relation['local_key'] ?? 'id';
                    $foreignKey = $relation['foreign_key'] ?? 'id';
                    $type = $relation['type'] ?? 'belongsTo';
                    $jsonPath = "data->{$localKey}";
                    
                    ModelsData::resolveRelationUsing($relationName, function ($query) use ($modelClass, $foreignKey, $jsonPath, $type) {
                        switch ($type) {
                            case 'hasOne':
                                return $query->hasOne($modelClass, $foreignKey, $jsonPath);
                            case 'hasMany':
                                return $query->hasMany($modelClass, $foreignKey, $jsonPath);
                            case 'belongsTo':
                            default:
                                return $query->belongsTo($modelClass, $jsonPath, $foreignKey);
                        }
                    });
                }
            }

            $relations = array_column($config->field_data['has'], 'name');

            $query->with($relations);
        }

        $query->with(['config']);
    }

    public static function filter($query, $config, $params = []) {

        $fields = $config->field_data['data'] ?? [];


        foreach ($fields as $item) {
            $field = $item['field'] ?? null;
            $where = $item['where'] ?? '=';
            $type = $item['type'] ?? 'text';

            if (!$field || !isset($params[$field])) {
                continue;
            }

            $value = $params[$field];

            if (!$value) {
                continue;
            }

            $jsonPath = "data->$field";

            switch ($where) {
                case '!=':
                    $query->where($jsonPath, '!=', $value);
                    break;
                case '>':
                case '<':
                case '>=':
                case '<=':
                    $query->where($jsonPath, $where, $value);
                    break;
                case 'like':
                    if (in_array($type, ['string'])) {
                        $query->where($jsonPath, 'like', '%' . $value . '%');
                    } elseif (is_array($value) && count($value) === 2) {
                        $query->whereBetween($jsonPath, [$value[0], $value[1]]);
                    }
                    break;
                case '=':
                default:
                    $query->where($jsonPath, (int) $value);
                    break;
            }
        }

        $data = collect($fields);

        foreach($params as $key => $value) {
            if (!str_ends_with($key, '_sort')) {
                continue;
            }
            $field =  substr($key, 0, -5);
            $item = $data->where('field', $field)->first();
            if (!$item) {
                continue;
            }
            if (!$item['sort']) {
                continue;
            }

            $jsonPath = "data->$field";
            
            switch ($value) {
                case 'asc':
                    $query->orderBy($jsonPath);
                    break;
                case 'desc':
                    $query->orderByDesc($jsonPath);
                    break;
            }
        }

        $query->orderBy('id', $params['id_sort'] === 'desc' ? 'desc' :'asc');
    }

    public static function format(Data $data, DataConfig $config) {

        $formatData = [
            "config_id" => $config->id,
        ];

        if (isset($data->parent_id)) {
            $formatData['parent_id'] = $data->parent_id;
        }

        $jsonData = [];
        
        foreach ($config->field_data['data'] as $fieldConfig) {
            $field = $fieldConfig['field'];
            $type = $fieldConfig['type'];
            $defaultValue = $fieldConfig['default_value'] ?? null;
            $value = $data->{$field} ?? $defaultValue;            
            $jsonData[$field] = self::formatSaveValue($value, $type);
        }

        $formatData['data'] = $jsonData;

        return $formatData;
    }

    private static function formatSaveValue($value, string $type) {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($type) {
            case 'int':
                return (int) $value;
            case 'decimal':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'string':
            case 'datetime':
            case 'date':
            default:
                return $value;
        }
    }
}
