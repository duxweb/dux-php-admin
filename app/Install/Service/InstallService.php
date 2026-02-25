<?php

declare(strict_types=1);

namespace App\Install\Service;

use App\System\Command\MenuCommand;
use Core\App;
use Core\Cloud\Package\Add as CloudAdd;
use Core\Cloud\Package\Package;
use Core\Cloud\Service\ConfigService;
use Core\Config\TomlLoader;
use Core\Config\TomlWriter;
use Core\Database\Db;
use Core\Handlers\ExceptionBusiness;
use Nette\Utils\FileSystem;
use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class InstallService
{
    private const INSTALL_LOCK_FILE = 'install.lock';
    private const RUNNING_LOCK_FILE = 'install.running';
    private const PENDING_DIR = 'install/pending';
    private const SQLITE_DATABASE_FILE = 'data/database.db';
    private const CLOUD_SERVERS = [
        'global' => 'https://cloud.dux.plus',
        'cn' => 'https://cn1.cloud.dux.plus',
    ];

    public function isInstalled(): bool
    {
        return is_file(data_path(self::INSTALL_LOCK_FILE));
    }

    public function prepare(array $payload): string
    {
        $appConfig = $this->validateAppConfig((array)($payload['app'] ?? []));
        $dbConfig = $this->validateDbConfig((array)($payload['db'] ?? []));
        $cloudServer = $this->resolveCloudServer((string)($payload['cloud_server'] ?? ''));

        $this->writeUseConfig($appConfig['name'], $appConfig['domain'], $appConfig['cloud_key'], $cloudServer['url']);
        $this->writeDatabaseConfig($dbConfig);
        $this->reloadRuntimeConfig();

        $token = bin2hex(random_bytes(16));
        $this->savePendingToken($token, [
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function getPendingToken(string $token): array
    {
        $token = $this->sanitizeToken($token);
        $file = $this->pendingTokenPath($token);

        if (!is_file($file)) {
            throw new ExceptionBusiness('Install token is invalid or expired', 400);
        }

        $content = file_get_contents($file);
        $data = json_decode((string)$content, true);
        if (!is_array($data)) {
            throw new ExceptionBusiness('Install token is invalid', 400);
        }

        return $data;
    }

    public function deletePendingToken(string $token): void
    {
        $token = $this->sanitizeToken($token);
        $file = $this->pendingTokenPath($token);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * @return resource|null
     */
    public function acquireRunningLock()
    {
        $file = data_path(self::RUNNING_LOCK_FILE);
        $handle = fopen($file, 'c+');
        if (!$handle) {
            throw new ExceptionBusiness('Unable to create running lock file');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }

        ftruncate($handle, 0);
        fwrite($handle, (string)getmypid());
        fflush($handle);

        return $handle;
    }

    /**
     * @param resource|null $handle
     */
    public function releaseRunningLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        @flock($handle, LOCK_UN);
        @fclose($handle);
        @unlink(data_path(self::RUNNING_LOCK_FILE));
    }

    public function markInstalled(): void
    {
        $payload = [
            'installed_at' => date('Y-m-d H:i:s'),
            'name' => (string)App::config('use')->get('app.name', ''),
            'domain' => (string)App::config('use')->get('app.domain', ''),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        file_put_contents(data_path(self::INSTALL_LOCK_FILE), $json ?: date('Y-m-d H:i:s'));

        $routeCache = data_path('cache/route.cache');
        if (is_file($routeCache)) {
            @unlink($routeCache);
        }
    }

    public function fetchCloudModules(?string $cloudKey = null, ?string $cloudServer = null): array
    {
        $server = $this->applyRuntimeCloudServer($cloudServer);
        $servers = $this->cloudServerStatus($server);

        $cloudKey = $this->resolveCloudKey($cloudKey);
        if ($cloudKey === '') {
            return [
                'enabled' => false,
                'list' => [],
                'server' => $server,
                'servers' => $servers,
            ];
        }

        $data = Package::request('get', '/v/package/version/list', [
            'query' => [
                'type' => ConfigService::getPackageType(),
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $cloudKey,
            ],
        ]);

        $rows = $this->extractCloudRows($data);
        $installedApps = $this->installedApps();
        $installedPackages = $this->installedPackages();
        $list = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $app = $this->normalizeApp((string)($row['app'] ?? ''));
            $name = trim((string)($row['name'] ?? ''));

            if (!$app && $name) {
                $app = $this->normalizeApp($name);
            }
            if (!$app) {
                continue;
            }

            $title = trim((string)($row['title'] ?? $row['label'] ?? $row['app_name'] ?? $app));
            $version = trim((string)($row['ver'] ?? $row['version'] ?? $row['latest'] ?? ''));
            $description = trim((string)($row['description'] ?? $row['subtitle'] ?? ''));
            $logo = trim((string)($row['logo'] ?? $row['icon'] ?? $row['image'] ?? ''));

            $isInstalled = in_array($app, $installedApps, true);
            if (!$isInstalled && $name !== '') {
                $isInstalled = in_array(strtolower($name), $installedPackages, true);
            }

            $list[] = [
                'app' => $app,
                'name' => $name ?: $app,
                'title' => $title,
                'version' => $version ?: '-',
                'description' => $description,
                'logo' => $logo,
                'installed' => $isInstalled,
            ];
        }

        return [
            'enabled' => true,
            'list' => array_values($list),
            'server' => $server,
            'servers' => $servers,
        ];
    }

    public function installCloudModules(
        array $packages,
        ?string $cloudKey = null,
        bool $upgradeInstalled = false,
        array $installedPackages = [],
        ?string $cloudServer = null
    ): array
    {
        $packageMaps = [];
        foreach ($packages as $item) {
            $name = $this->normalizePackageName((string)$item);
            if ($name === '') {
                continue;
            }
            $packageMaps[$name] = 'latest';
        }

        if ($upgradeInstalled) {
            foreach ($installedPackages as $item) {
                $name = $this->normalizePackageName((string)$item);
                if ($name === '') {
                    continue;
                }
                $packageMaps[$name] = 'latest';
            }
        }

        if (!$packageMaps) {
            return [
                'installed' => [],
                'logs' => [],
            ];
        }

        $cloudKey = $this->resolveCloudKey($cloudKey);
        if ($cloudKey === '') {
            throw new ExceptionBusiness('Cloud key is required');
        }

        $this->applyRuntimeCloudKey($cloudKey);
        $this->applyRuntimeCloudServer($cloudServer);

        $output = new BufferedOutput();
        $installed = [];

        try {
            $output->writeln(($upgradeInstalled ? 'Install and update modules: ' : 'Install modules: ') . implode(', ', array_keys($packageMaps)));
            CloudAdd::main($output, $packageMaps, $upgradeInstalled);
            $installed = array_keys($packageMaps);
        } finally {
            FileSystem::delete(ConfigService::getTempDir());
        }

        return [
            'installed' => $installed,
            'logs' => $this->splitLogs($output->fetch()),
        ];
    }

    public function runComposerUpdate(OutputInterface $output): void
    {
        $phpBinary = $this->resolvePhpCliBinary();
        $composerScript = $this->resolveLocalComposerScript();
        $output->writeln('[env] php cli: ' . $phpBinary);
        $output->writeln('[env] composer: ' . $composerScript);

        $process = new Process([$phpBinary, $composerScript, 'update', '--no-interaction', '--no-progress'], base_path());
        $process->setTimeout(ConfigService::getCommandTimeout());
        $process->run(function ($type, $buffer) use ($output): void {
            $output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function syncDatabase(OutputInterface $output): void
    {
        $this->resetSqliteDatabase($output);

        $migrate = App::dbMigrate();
        if (!$migrate->migrate) {
            $migrate->registerAttribute();
        }
        $migrate->migrate($output, '');
        $output->writeln('<info>Sync database successfully</info>');
    }

    public function syncMenus(OutputInterface $output): void
    {
        $command = new MenuCommand();
        $status = $command->run(new ArrayInput([]), $output);
        if ($status !== Command::SUCCESS) {
            throw new ExceptionBusiness('Menu sync failed');
        }
    }

    private function validateAppConfig(array $app): array
    {
        $name = trim((string)($app['name'] ?? ''));
        if ($name === '') {
            throw new ExceptionBusiness('System name is required');
        }

        $domain = trim((string)($app['domain'] ?? ''));
        if ($domain === '') {
            throw new ExceptionBusiness('System domain is required');
        }

        if (!preg_match('#^https?://#i', $domain)) {
            $domain = 'http://' . $domain;
        }

        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            throw new ExceptionBusiness('System domain is invalid');
        }

        $cloudKey = trim((string)($app['cloud_key'] ?? ''));

        return [
            'name' => $name,
            'domain' => $domain,
            'cloud_key' => $cloudKey,
        ];
    }

    private function validateDbConfig(array $db): array
    {
        $driver = strtolower(trim((string)($db['driver'] ?? 'mysql')));
        if (!in_array($driver, ['mysql', 'sqlite', 'pgsql'], true)) {
            throw new ExceptionBusiness('Database driver must be mysql, sqlite or pgsql');
        }

        $prefix = (string)($db['prefix'] ?? 'app_');
        if ($prefix === '') {
            $prefix = 'app_';
        }

        if ($driver === 'sqlite') {
            $database = self::SQLITE_DATABASE_FILE;
            $absoluteFile = base_path($database);
            $dir = dirname($absoluteFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            if (!is_file($absoluteFile)) {
                touch($absoluteFile);
            }
            return [
                'driver' => $driver,
                'database' => $database,
                'prefix' => $prefix,
            ];
        }

        $database = trim((string)($db['database'] ?? ''));
        if ($database === '') {
            throw new ExceptionBusiness('Database name is required');
        }

        $host = trim((string)($db['host'] ?? ''));
        if ($host === '') {
            throw new ExceptionBusiness('Database host is required');
        }

        $username = trim((string)($db['username'] ?? ''));
        if ($username === '') {
            throw new ExceptionBusiness('Database username is required');
        }

        $defaultPort = $driver === 'pgsql' ? 5432 : 3306;
        $port = (int)($db['port'] ?? $defaultPort);
        if ($port <= 0) {
            $port = $defaultPort;
        }

        return [
            'driver' => $driver,
            'database' => $database,
            'prefix' => $prefix,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => (string)($db['password'] ?? ''),
        ];
    }

    private function writeUseConfig(string $name, string $domain, string $cloudKey, string $cloudUrl): void
    {
        $file = $this->configFilePath('use');
        $config = App::config('use', false);
        $config->set('app.name', $name);
        $config->set('app.domain', $domain);
        $config->set('app.secret', $this->generateSecretKey());
        $config->set('cloud.key', $cloudKey);
        $config->set('cloud.url', $cloudUrl);
        $config->toFile($file, new TomlWriter());
    }

    private function writeDatabaseConfig(array $dbConfig): void
    {
        $file = $this->configFilePath('database');
        $config = App::config('database', false);
        $config->set('db.drivers.default', $dbConfig);
        $config->toFile($file, new TomlWriter());
    }

    private function reloadRuntimeConfig(): void
    {
        $di = App::di();

        $useFile = $this->configFilePath('use');
        $databaseFile = $this->configFilePath('database');

        $di->set('config.use.true', new Config($useFile, new TomlLoader(true)));
        $di->set('config.use.false', new Config($useFile, new TomlLoader(false)));

        $di->set('config.database.true', new Config($databaseFile, new TomlLoader(true)));
        $di->set('config.database.false', new Config($databaseFile, new TomlLoader(false)));

        $drivers = App::config('database')->get('db.drivers', []);
        $db = Db::init($drivers);
        $db->getConnection()->getPdo();
        $di->set('db', $db);
    }

    private function configFilePath(string $name): string
    {
        $devFile = config_path($name . '.dev.toml');
        if (is_file($devFile)) {
            return $devFile;
        }
        return config_path($name . '.toml');
    }

    private function savePendingToken(string $token, array $payload): void
    {
        $dir = data_path(self::PENDING_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->pendingTokenPath($token), json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function pendingTokenPath(string $token): string
    {
        return data_path(self::PENDING_DIR . '/' . $token . '.json');
    }

    private function sanitizeToken(string $token): string
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new ExceptionBusiness('Install token is invalid', 400);
        }
        return $token;
    }

    private function resolveCloudKey(?string $cloudKey = null): string
    {
        $value = trim((string)($cloudKey ?? ''));
        if ($value !== '') {
            return $value;
        }
        return trim((string)ConfigService::getKey());
    }

    private function applyRuntimeCloudKey(string $cloudKey): void
    {
        App::config('use')->set('cloud.key', $cloudKey);
    }

    private function applyRuntimeCloudServer(?string $server = null): string
    {
        $resolved = $this->resolveCloudServer($server);
        ConfigService::init([
            'api' => [
                'url' => $resolved['url'],
            ],
        ]);
        App::config('use')->set('cloud.url', $resolved['url']);
        return $resolved['key'];
    }

    /**
     * @return array{key:string,url:string}
     */
    private function resolveCloudServer(?string $server = null): array
    {
        $value = strtolower(trim((string)$server));
        if ($value === '') {
            $value = trim((string)App::config('use')->get('cloud.url', ''));
        }

        if ($value !== '') {
            if (isset(self::CLOUD_SERVERS[$value])) {
                return [
                    'key' => $value,
                    'url' => self::CLOUD_SERVERS[$value],
                ];
            }
            foreach (self::CLOUD_SERVERS as $key => $url) {
                if (rtrim(strtolower($url), '/') === rtrim(strtolower($value), '/')) {
                    return [
                        'key' => $key,
                        'url' => $url,
                    ];
                }
            }
        }

        return [
            'key' => 'global',
            'url' => self::CLOUD_SERVERS['global'],
        ];
    }

    private function cloudServerStatus(string $selectedServer): array
    {
        $rows = [];
        foreach (self::CLOUD_SERVERS as $key => $url) {
            $rows[] = [
                'key' => $key,
                'title' => parse_url($url, PHP_URL_HOST) ?: $url,
                'url' => $url,
                'latency_ms' => $this->cloudServerLatency($url),
                'selected' => $selectedServer === $key,
            ];
        }
        return $rows;
    }

    private function cloudServerLatency(string $url): ?int
    {
        if (!function_exists('stream_socket_client')) {
            return null;
        }
        $host = (string)(parse_url($url, PHP_URL_HOST) ?: '');
        if ($host === '') {
            return null;
        }
        $port = (int)(parse_url($url, PHP_URL_PORT) ?: 443);
        $start = microtime(true);
        $errno = 0;
        $errstr = '';
        $client = @stream_socket_client(
            sprintf('tcp://%s:%d', $host, $port),
            $errno,
            $errstr,
            2,
            STREAM_CLIENT_CONNECT
        );
        if (!is_resource($client)) {
            return null;
        }
        fclose($client);
        return (int)round((microtime(true) - $start) * 1000);
    }

    private function normalizeApp(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $name = (string)end($parts);
        }
        $name = strtolower($name);
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            return '';
        }
        return $name;
    }

    private function normalizePackageName(string $name): string
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return '';
        }
        if (!preg_match('/^[a-z0-9._-]+(?:\/[a-z0-9._-]+)?$/', $name)) {
            return '';
        }
        return $name;
    }

    private function extractCloudRows(array $data): array
    {
        if (isset($data['list']) && is_array($data['list'])) {
            return $data['list'];
        }
        if (isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }
        if (array_is_list($data)) {
            return $data;
        }
        return [];
    }

    private function installedApps(): array
    {
        $apps = [];
        $registers = (array)App::config('app')->get('registers', []);
        foreach ($registers as $name) {
            if (!is_string($name) || !str_starts_with($name, 'App\\')) {
                continue;
            }
            $parts = explode('\\', $name);
            $module = strtolower((string)($parts[1] ?? ''));
            if ($module && !in_array($module, ['system', 'data', 'install'], true)) {
                $apps[] = $module;
            }
        }
        return array_values(array_unique($apps));
    }

    private function installedPackages(): array
    {
        $file = base_path('app.json');
        if (!is_file($file)) {
            return [];
        }
        $content = file_get_contents($file);
        $data = json_decode((string)$content, true);
        $dependencies = (array)($data['dependencies'] ?? []);
        return array_values(array_map('strtolower', array_keys($dependencies)));
    }

    private function splitLogs(string $logs): array
    {
        $rows = preg_split('/\r\n|\r|\n/', $logs);
        if (!$rows) {
            return [];
        }
        $result = [];
        foreach ($rows as $line) {
            $line = trim((string)$line);
            if ($line !== '') {
                $result[] = $line;
            }
        }
        return $result;
    }

    private function resolveLocalComposerScript(): string
    {
        $candidates = [
            base_path('vendor/composer/composer/bin/composer'),
            base_path('vendor/bin/composer'),
        ];

        foreach ($candidates as $file) {
            $file = (string)$file;
            if ($file === '' || !is_file($file)) {
                continue;
            }
            if (str_ends_with(strtolower($file), '.bat')) {
                continue;
            }
            return $file;
        }

        throw new ExceptionBusiness('Local composer script not found in vendor directory');
    }

    private function resolvePhpCliBinary(): string
    {
        $finder = new PhpExecutableFinder();
        $finderBinary = trim((string)$finder->find(false));
        $envBinary = trim((string)getenv('PHP_CLI_BINARY'));
        $runtimeBinary = defined('PHP_BINARY') ? trim((string)PHP_BINARY) : '';
        $bindirBinary = defined('PHP_BINDIR')
            ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php')
            : '';
        $runtimeDirBinary = '';
        if ($runtimeBinary !== '' && str_contains($runtimeBinary, DIRECTORY_SEPARATOR)) {
            $runtimeDirBinary = dirname(dirname($runtimeBinary)) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php');
        }
        $pathBinary = (new ExecutableFinder())->find(DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php') ?: '';

        $candidates = [
            $finderBinary,
            $envBinary,
            $runtimeBinary,
            $bindirBinary,
            $runtimeDirBinary,
            $pathBinary,
        ];

        foreach ($candidates as $binary) {
            if ($binary === '' || !$this->isCliPhpBinary($binary)) {
                continue;
            }
            if (str_contains($binary, DIRECTORY_SEPARATOR)) {
                if (!is_file($binary) || !is_executable($binary)) {
                    continue;
                }
                return $binary;
            }
            return $binary;
        }

        throw new ExceptionBusiness('PHP CLI binary not found');
    }

    private function isCliPhpBinary(string $binary): bool
    {
        $name = strtolower(basename(str_replace('\\', '/', $binary)));
        return !in_array($name, ['php-fpm', 'php-fpm.exe', 'php-cgi', 'php-cgi.exe'], true);
    }

    private function generateSecretKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function resetSqliteDatabase(OutputInterface $output): void
    {
        $driver = strtolower((string)App::config('database')->get('db.drivers.default.driver', ''));
        if ($driver !== 'sqlite') {
            return;
        }

        $database = trim((string)App::config('database')->get('db.drivers.default.database', self::SQLITE_DATABASE_FILE));
        if ($database === '') {
            $database = self::SQLITE_DATABASE_FILE;
        }

        $file = $this->resolveDatabasePath($database);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->disconnectRuntimeDatabase();
        if (is_file($file)) {
            if (!@unlink($file)) {
                throw new ExceptionBusiness('Unable to reset sqlite database file');
            }
            $output->writeln('<comment>SQLite database reset: ' . $database . '</comment>');
        } else {
            $output->writeln('<comment>SQLite database init: ' . $database . '</comment>');
        }
        if (!is_file($file) && !@touch($file)) {
            throw new ExceptionBusiness('Unable to initialize sqlite database file');
        }
        @chmod($file, 0666);

        $this->reloadRuntimeConfig();
    }

    private function disconnectRuntimeDatabase(): void
    {
        $di = App::di();
        if (!$di->has('db')) {
            return;
        }
        try {
            $di->get('db')->getConnection()->disconnect();
        } catch (\Throwable) {
        }
    }

    private function resolveDatabasePath(string $database): string
    {
        if (preg_match('#^(?:[A-Za-z]:[\\\\/]|/)#', $database)) {
            return $database;
        }
        return base_path($database);
    }
}
