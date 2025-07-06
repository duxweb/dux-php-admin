<?php

namespace App\System\Service;

use Illuminate\Support\Str;

class Config
{

    private static string $model = \App\System\Models\Config::class;
    private static ?array $config = null;

    public static function getJsonValue(string $name, mixed $default = null)
    {
        $value = self::getValue($name);
        if (is_null($value)) return $default;
        return json_decode($value, true);
    }

    public static function getValue(string $name, mixed $default = null): mixed
    {
        $config = self::getConfig();

        if (Str::contains($name, '.')) {
            $parts = explode('.', $name, 2);
            $config = $config[$parts[0]];
            $name = $parts[1];
            if (is_string($config) && json_validate($config)) {
                $config = json_decode($config, true);
            }
        }
        return data_get($config, $name, $default);
    }

    public static function setValue(string $name, mixed $value): void
    {
        (new self::$model)->updateOrInsert(
            ["name" => $name],
            ["value" => is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value]
        );
        self::$config = null;
    }

    private static function getConfig(): ?array
    {
        if (self::$config) {
            return self::$config;
        }
        $list = (new self::$model)->query()->get();
        foreach ($list as $item) {
            self::$config[$item->name] = $item->value;
        }
        return self::$config;
    }
}
