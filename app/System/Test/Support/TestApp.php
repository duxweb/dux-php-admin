<?php

declare(strict_types=1);

namespace App\System\Test\Support;

use Core\App as CoreApp;
use Core\Database\Migrate;
use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Console\Output\NullOutput;

final class TestApp
{
    private static bool $booted = false;

    /**
     * @var array<int, class-string>
     */
    private static array $migrateProviders = [
        \App\System\Test\Support\Migrate\SystemMigrateProvider::class,
    ];

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
    }

    /**
     * @param array<int, class-string> $providers
     */
    public static function setMigrateProviders(array $providers): void
    {
        self::$migrateProviders = array_values($providers);
    }

    /**
     * @param array<int, class-string> $providers
     */
    public static function addMigrateProviders(array $providers): void
    {
        if (!$providers) {
            return;
        }
        self::$migrateProviders = array_values(array_unique(array_merge(self::$migrateProviders, $providers)));
    }

    public static function refreshDatabase(): void
    {
        // Re-create application container each test, so previous DB bindings/config do not leak.
        $basePath = dirname(__DIR__, 4);
        $envPath = $basePath . '/.env';
        $createdEnv = false;
        if (!is_file($envPath)) {
            file_put_contents($envPath, '');
            $createdEnv = true;
        }

        $previousReporting = error_reporting();
        error_reporting($previousReporting & ~E_WARNING);
        try {
            CoreApp::create($basePath, debug: true);
        } finally {
            error_reporting($previousReporting);
        }

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ], 'default');

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        CoreApp::di()->set('db', $capsule);

        $migrate = new Migrate();
        foreach (self::resolveMigrateModels() as $model) {
            $migrate->register($model);
        }
        $migrate->migrate(new NullOutput());

        if ($createdEnv) {
            @unlink($envPath);
        }
    }

    /**
     * @return array<int, class-string>
     */
    private static function resolveMigrateModels(): array
    {
        $models = [];
        foreach (self::$migrateProviders as $provider) {
            if (!class_exists($provider)) {
                continue;
            }
            $list = $provider::models();
            if (!is_array($list)) {
                continue;
            }
            $models = array_merge($models, $list);
        }
        return $models;
    }
}
