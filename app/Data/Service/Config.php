<?php

declare(strict_types=1);

namespace App\Data\Service;

use App\Data\Models\DataConfig;
use Core\Validator\Data;

class Config
{
    public static function filter($query, $config, $params = []) {

        $query->where('config_id', $config->id);

        $filters = $config->table_data['filters'] ?? [];

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $where = $filter['where'] ?? '=';
            $type = $filter['type'] ?? 'text';
            $name = $filter['name'] ?? $field;

            if (!$field || !isset($params[$name])) {
                continue;
            }

            $value = $params[$name];

            if ($value === null || $value === '') {
                continue;
            }

            $jsonPath = "data->$field";

            switch ($type) {
                case 'text':
                    if ($where === 'like') {
                        $query->where($jsonPath, 'like', '%' . $value . '%');
                    } else {
                        $query->where($jsonPath, $where, $value);
                    }
                    break;

                case 'select':
                case 'async-select':
                case 'cascader':
                    if (is_array($value)) {
                        if ($where === '!=') {
                            $query->where(function($q) use ($jsonPath, $value) {
                                foreach ($value as $v) {
                                    $q->where($jsonPath, '!=', $v);
                                }
                            });
                        } else {
                            $query->where(function($q) use ($jsonPath, $value) {
                                foreach ($value as $v) {
                                    $q->orWhere($jsonPath, '=', $v);
                                }
                            });
                        }
                    } else {
                        $query->where($jsonPath, $where, $value);
                    }
                    break;

                case 'daterange':
                    if (is_array($value) && count($value) === 2) {
                        $startDate = $value[0];
                        $endDate = $value[1];

                        if ($startDate && $endDate) {
                            $query->whereBetween($jsonPath, [$startDate, $endDate]);
                        } elseif ($startDate) {
                            $query->where($jsonPath, '>=', $startDate);
                        } elseif ($endDate) {
                            $query->where($jsonPath, '<=', $endDate);
                        }
                    }
                    break;

                default:
                    // 默认处理
                    if ($where === 'like') {
                        $query->where($jsonPath, 'like', '%' . $value . '%');
                    } else {
                        $query->where($jsonPath, $where, $value);
                    }
                    break;
            }
        }

        $query->orderBy('id');
    }

    public static function format(Data $data, DataConfig $config) {

        $formatData = [
            "config_id" => $config->id,
        ];

        // 处理父级ID
        if (isset($data->parent_id)) {
            $formatData['parent_id'] = $data->parent_id;
        }

        $systemFields = ['id', 'parent_id', 'config_id', 'user_type', 'user_id', 'created_at', 'updated_at'];
        $jsonData = [];
        foreach ($data->toArray() as $key => $value) {
            if (!in_array($key, $systemFields)) {
                $jsonData[$key] = $value;
            }
        }

        $formatData['data'] = $jsonData;

        return $formatData;
    }
}
