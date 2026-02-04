<?php

declare(strict_types=1);

namespace App\System\Test\Support\Migrate;

final class SystemMigrateProvider
{
    /**
     * @return array<int, class-string>
     */
    public static function models(): array
    {
        return [
            \App\System\Models\Config::class,
        ];
    }
}
