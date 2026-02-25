<?php

declare(strict_types=1);

namespace App\Install\Service;

use App\System\Command\MenuCommand;
use App\System\Command\MenuUninstallCommand;
use Core\App;
use Core\Cloud\Package\Add as CloudAdd;
use Core\Cloud\Package\Del as CloudDel;
use Core\Cloud\Package\Package;
use Core\Cloud\Service\ConfigService;
use Core\Handlers\ExceptionBusiness;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CloudModuleService
{
    private const STORE_RUNNING_LOCK_FILE = 'store.running';
    private const STORE_PENDING_DIR = 'install/store/pending';
    private const CLOUD_SERVERS = [
        'global' => 'https://cloud.dux.plus',
        'cn' => 'https://cn1.cloud.dux.plus',
    ];
    private const PROTECTED_MODULES = ['system', 'data'];

    private ?InstallService $installService = null;

    public function listModules(?string $cloudKey = null, ?string $cloudServer = null): array
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
        $installedVersions = $this->installedVersions();
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
            $latestVersion = trim((string)($row['ver'] ?? $row['version'] ?? $row['latest'] ?? ''));
            $description = trim((string)($row['description'] ?? $row['subtitle'] ?? ''));
            $logo = trim((string)($row['logo'] ?? $row['icon'] ?? $row['image'] ?? ''));
            $id = (int)($row['id'] ?? 0);

            $isInstalled = in_array($app, $installedApps, true);
            if (!$isInstalled && $name !== '') {
                $isInstalled = in_array(strtolower($name), $installedPackages, true);
            }

            $installedVersion = (string)($installedVersions['app'][$app] ?? '');
            if ($installedVersion === '' && $name !== '') {
                $installedVersion = (string)($installedVersions['name'][strtolower($name)] ?? '');
            }

            $list[] = [
                'id' => $id,
                'app' => $app,
                'name' => $name ?: $app,
                'title' => $title,
                'version' => $latestVersion ?: '-',
                'latest_version' => $latestVersion ?: '-',
                'installed_version' => $installedVersion,
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

    public function moduleDetail(string $identifier, ?string $cloudKey = null, ?string $cloudServer = null): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            throw new ExceptionBusiness('Invalid module id');
        }

        $listData = $this->listModules($cloudKey, $cloudServer);
        $module = null;
        $list = (array)($listData['list'] ?? []);

        if (ctype_digit($identifier)) {
            $id = (int)$identifier;
            foreach ($list as $item) {
                if ((int)($item['id'] ?? 0) === $id) {
                    $module = $item;
                    break;
                }
            }
        }

        if (!$module) {
            $app = $this->normalizeApp($identifier);
            if ($app !== '') {
                foreach ($list as $item) {
                    if (($item['app'] ?? '') === $app) {
                        $module = $item;
                        break;
                    }
                }
            }
        }

        if (!$module) {
            throw new ExceptionBusiness('Module not found');
        }

        $cloudKey = $this->resolveCloudKey($cloudKey);
        if ($cloudKey === '') {
            return $this->formatModuleDetail($module, []);
        }

        $this->applyRuntimeCloudServer($cloudServer);
        $detail = $this->fetchCloudDetail((int)($module['id'] ?? 0), $cloudKey);
        $app = (string)($module['app'] ?? '');
        if (!$detail && $app !== '') {
            $detail = Package::app($app);
        }

        return $this->formatModuleDetail($module, $detail);
    }

    public function installModules(
        array $packages,
        ?string $cloudKey = null,
        bool $upgradeInstalled = false,
        array $installedPackages = [],
        ?string $cloudServer = null
    ): array {
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

    public function prepareStoreAction(array $payload, ?string $cloudKey = null, ?string $cloudServer = null): string
    {
        $app = $this->normalizeApp((string)($payload['app'] ?? ''));
        if ($app === '') {
            throw new ExceptionBusiness('Invalid app name');
        }

        $action = strtolower(trim((string)($payload['action'] ?? 'install')));
        if (!in_array($action, ['install', 'upgrade', 'uninstall'], true)) {
            throw new ExceptionBusiness('Invalid action type');
        }
        if ($action === 'uninstall' && $this->isProtectedModule($app)) {
            throw new ExceptionBusiness('System/Data module cannot be uninstalled');
        }

        $tasks = $this->normalizeTasks((array)($payload['tasks'] ?? []));
        $resolvedServer = $this->resolveCloudServer($cloudServer ?: (string)($payload['cloud_server'] ?? ''));
        $token = bin2hex(random_bytes(16));
        $this->saveStoreToken($token, [
            'app' => $app,
            'action' => $action,
            'tasks' => $tasks,
            'cloud_key' => $this->resolveCloudKey($cloudKey),
            'cloud_server' => $resolvedServer['key'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function runStoreActionToken(string $token, OutputInterface $output): array
    {
        $data = $this->getStoreToken($token);
        $executed = $this->installOrUpgrade(
            (string)($data['app'] ?? ''),
            (string)($data['action'] ?? 'install'),
            (array)($data['tasks'] ?? []),
            $output,
            (string)($data['cloud_key'] ?? ''),
            (string)($data['cloud_server'] ?? '')
        );

        return [
            'app' => (string)($data['app'] ?? ''),
            'action' => (string)($data['action'] ?? 'install'),
            'tasks_executed' => $executed,
        ];
    }

    public function installOrUpgrade(
        string $app,
        string $action,
        array $options,
        OutputInterface $output,
        ?string $cloudKey = null,
        ?string $cloudServer = null
    ): array {
        $app = $this->normalizeApp($app);
        if ($app === '') {
            throw new ExceptionBusiness('Invalid app name');
        }

        $action = strtolower(trim($action));
        if (!in_array($action, ['install', 'upgrade', 'uninstall'], true)) {
            throw new ExceptionBusiness('Invalid action type');
        }
        if ($action === 'uninstall' && $this->isProtectedModule($app)) {
            throw new ExceptionBusiness('System/Data module cannot be uninstalled');
        }

        $tasks = $this->normalizeTasks($options);
        $cloudKey = $this->resolveCloudKey($cloudKey);
        if ($cloudKey === '') {
            throw new ExceptionBusiness('Cloud key is required');
        }

        $this->applyRuntimeCloudKey($cloudKey);
        $this->applyRuntimeCloudServer($cloudServer);

        $executed = [
            'composer' => false,
            'sync_menu' => false,
            'sync_db' => false,
        ];

        try {
            if ($action === 'uninstall') {
                $output->writeln('[step] menu:uninstall ' . $app);
                $this->uninstallModuleMenus($app, $output);
                $output->writeln('[done] menu:uninstall ' . $app);
                $executed['sync_menu'] = true;
            }

            $output->writeln(sprintf('[step] module %s: %s', $action, $app));
            $packageName = $this->modulePackageName($app, $cloudKey, $cloudServer);
            if ($packageName !== '') {
                if ($action === 'uninstall') {
                    CloudDel::main($output, [$packageName => 'latest']);
                } else {
                    CloudAdd::main($output, [$packageName => 'latest'], $action === 'upgrade');
                }
            } elseif ($action === 'upgrade') {
                CloudAdd::main($output, [], true);
            } else {
                throw new ExceptionBusiness('Module package not found');
            }
            $output->writeln(sprintf('[done] module %s: %s', $action, $app));

            if ($tasks['composer'] && $action !== 'uninstall') {
                $output->writeln('[step] composer update');
                $this->installService()->runComposerUpdate($output);
                $output->writeln('[done] composer update');
                $executed['composer'] = true;
            }

            if ($tasks['sync_menu'] && $action !== 'uninstall') {
                $output->writeln('[step] menu:sync ' . $app);
                $this->syncModuleMenus($app, $output);
                $output->writeln('[done] menu:sync ' . $app);
                $executed['sync_menu'] = true;
            }

            if ($tasks['sync_db'] && $action !== 'uninstall') {
                $output->writeln('[step] db:sync ' . $app);
                $this->syncModuleDatabase($app, $output);
                $output->writeln('[done] db:sync ' . $app);
                $executed['sync_db'] = true;
            }

            return $executed;
        } finally {
            FileSystem::delete(ConfigService::getTempDir());
        }
    }

    private function modulePackageName(string $app, string $cloudKey, ?string $cloudServer): string
    {
        try {
            $listData = $this->listModules($cloudKey, $cloudServer);
            $list = (array)($listData['list'] ?? []);
            foreach ($list as $item) {
                if (($item['app'] ?? '') !== $app) {
                    continue;
                }
                return $this->normalizePackageName((string)($item['name'] ?? ''));
            }
        } catch (\Throwable) {
        }
        return '';
    }

    public function acquireStoreRunningLock()
    {
        $file = data_path(self::STORE_RUNNING_LOCK_FILE);
        $handle = fopen($file, 'c+');
        if (!$handle) {
            throw new ExceptionBusiness('Unable to create store running lock file');
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

    public function releaseStoreRunningLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }
        @flock($handle, LOCK_UN);
        @fclose($handle);
        @unlink(data_path(self::STORE_RUNNING_LOCK_FILE));
    }

    public function getStoreToken(string $token): array
    {
        $token = $this->sanitizeToken($token);
        $file = $this->storeTokenPath($token);
        if (!is_file($file)) {
            throw new ExceptionBusiness('Store action token is invalid or expired', 400);
        }
        $content = file_get_contents($file);
        $data = json_decode((string)$content, true);
        if (!is_array($data)) {
            throw new ExceptionBusiness('Store action token is invalid', 400);
        }
        return $data;
    }

    public function deleteStoreToken(string $token): void
    {
        $token = $this->sanitizeToken($token);
        $file = $this->storeTokenPath($token);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function syncModuleMenus(string $module, OutputInterface $output): void
    {
        $command = new MenuCommand();
        $status = $command->run(new ArrayInput([
            'module' => $module,
            'app' => 'admin',
        ]), $output);
        if ($status !== Command::SUCCESS) {
            throw new ExceptionBusiness('Module menu sync failed');
        }
    }

    private function syncModuleDatabase(string $module, OutputInterface $output): void
    {
        $migrate = App::dbMigrate();
        if (!$migrate->migrate) {
            $migrate->registerAttribute();
        }
        $migrate->migrate($output, $module);
    }

    private function uninstallModuleMenus(string $module, OutputInterface $output): void
    {
        $command = new MenuUninstallCommand();
        $status = $command->run(new ArrayInput([
            'module' => $module,
        ]), $output);
        if ($status !== Command::SUCCESS) {
            throw new ExceptionBusiness('Module menu uninstall failed');
        }
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

    private function installedVersions(): array
    {
        $result = [
            'app' => [],
            'name' => [],
        ];
        $file = base_path('app.lock');
        if (!is_file($file)) {
            return $result;
        }
        $content = file_get_contents($file);
        $data = json_decode((string)$content, true);
        $packages = (array)($data['packages'] ?? []);
        foreach ($packages as $item) {
            if (!is_array($item)) {
                continue;
            }
            $version = trim((string)($item['version'] ?? ''));
            if ($version === '') {
                continue;
            }
            $app = $this->normalizeApp((string)($item['app'] ?? ''));
            if ($app !== '') {
                $result['app'][$app] = $version;
            }
            $name = strtolower(trim((string)($item['name'] ?? '')));
            if ($name !== '') {
                $result['name'][$name] = $version;
            }
        }
        return $result;
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

    private function isProtectedModule(string $app): bool
    {
        return in_array(strtolower(trim($app)), self::PROTECTED_MODULES, true);
    }

    private function normalizeTasks(array $tasks): array
    {
        return [
            'composer' => (bool)($tasks['composer'] ?? true),
            'sync_menu' => (bool)($tasks['sync_menu'] ?? true),
            'sync_db' => (bool)($tasks['sync_db'] ?? true),
        ];
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

    private function saveStoreToken(string $token, array $payload): void
    {
        $dir = data_path(self::STORE_PENDING_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->storeTokenPath($token), json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function storeTokenPath(string $token): string
    {
        return data_path(self::STORE_PENDING_DIR . '/' . $token . '.json');
    }

    private function sanitizeToken(string $token): string
    {
        $token = strtolower(trim($token));
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new ExceptionBusiness('Store action token is invalid', 400);
        }
        return $token;
    }

    private function installService(): InstallService
    {
        if (!$this->installService) {
            $this->installService = new InstallService();
        }
        return $this->installService;
    }

    private function fetchCloudDetail(int $id, string $cloudKey): array
    {
        if ($id <= 0) {
            return [];
        }

        $paths = [
            '/v/package/version/detail/' . $id,
            '/v/package/detail/' . $id,
        ];

        foreach ($paths as $path) {
            try {
                $data = Package::request('get', $path, [
                    'query' => [
                        'type' => ConfigService::getPackageType(),
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => $cloudKey,
                    ],
                ]);
                if (is_array($data) && $data) {
                    return $data;
                }
            } catch (\Throwable) {
            }
        }

        return [];
    }

    private function formatModuleDetail(array $module, array $detail): array
    {
        $payload = isset($detail['data']) && is_array($detail['data']) ? $detail['data'] : $detail;
        $info = isset($payload['info']) && is_array($payload['info']) ? $payload['info'] : [];

        $tags = $payload['tags'] ?? $info['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }

        $kits = $payload['kits'] ?? [];
        if (!is_array($kits)) {
            $kits = [];
        }

        return [
            'id' => (int)($module['id'] ?? 0),
            'app' => (string)($module['app'] ?? ''),
            'name' => (string)($info['name'] ?? $module['name'] ?? ''),
            'title' => (string)($info['label'] ?? $module['title'] ?? ''),
            'version' => (string)($info['ver'] ?? $module['version'] ?? ''),
            'latest_version' => (string)($info['ver'] ?? $module['latest_version'] ?? ''),
            'installed_version' => (string)($module['installed_version'] ?? ''),
            'description' => (string)($info['description'] ?? $module['description'] ?? ''),
            'logo' => (string)($info['logo'] ?? $module['logo'] ?? ''),
            'installed' => (bool)($module['installed'] ?? false),
            'ver_type' => (string)($info['ver_type'] ?? ''),
            'type' => (string)($info['type'] ?? ''),
            'readme' => (string)($info['readme'] ?? ''),
            'changelog' => (string)($payload['changelog'] ?? $info['changelog'] ?? ''),
            'download_num' => (int)($info['download_num'] ?? 0),
            'ver_at' => (string)($info['ver_at'] ?? ''),
            'created_at' => (string)($info['created_at'] ?? ''),
            'nickname' => (string)($info['nickname'] ?? ''),
            'tags' => $tags,
            'kits' => $kits,
        ];
    }
}
