<?php

declare(strict_types=1);

namespace App\Install\Web;

use App\Install\Service\SseCommandOutput;
use App\Install\Service\InstallService;
use Core\App;
use Core\Handlers\ExceptionBusiness;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\NonBufferedBody;

#[RouteGroup(app: 'web', route: '/install', name: 'install')]
class Wizard
{
    private const STEP_ORDER = [
        1 => 'license',
        2 => 'environment',
        3 => 'system',
        4 => 'database',
        5 => 'modules',
        6 => 'run',
    ];

    private const STEP_PATHS = [
        'license' => '/install/license',
        'environment' => '/install/environment',
        'system' => '/install/system',
        'database' => '/install/database',
        'modules' => '/install/modules',
        'run' => '/install/run',
    ];

    private const STEP_COOKIE = 'dux_install_step';
    private const LANG_COOKIE = 'dux_install_lang';
    private const COOKIE_AGE = 2592000;
    private const SUPPORT_LANGS = ['zh-CN', 'en-US'];

    private ?InstallService $service = null;

    #[Route(methods: 'GET', route: '')]
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($this->service()->isInstalled()) {
            return $this->redirect($response, '/');
        }
        return $this->redirect($response, self::STEP_PATHS['license']);
    }

    #[Route(methods: 'GET', route: '/license')]
    public function license(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->renderStep($request, $response, 'license');
    }

    #[Route(methods: 'GET', route: '/environment')]
    public function environment(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->renderStep($request, $response, 'environment');
    }

    #[Route(methods: 'GET', route: '/system')]
    public function system(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->renderStep($request, $response, 'system');
    }

    #[Route(methods: 'GET', route: '/database')]
    public function database(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->renderStep($request, $response, 'database');
    }

    #[Route(methods: 'GET', route: '/run')]
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->renderStep($request, $response, 'run');
    }

    #[Route(methods: 'GET', route: '/modules')]
    public function modules(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->renderStep($request, $response, 'modules');
    }

    // 兼容旧路径
    #[Route(methods: 'GET', route: '/basic')]
    public function legacyBasic(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->redirect($response, self::STEP_PATHS['system']);
    }

    #[Route(methods: 'GET', route: '/process')]
    public function legacyProcess(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->redirect($response, self::STEP_PATHS['run']);
    }

    #[Route(methods: 'GET', route: '/next/{step}')]
    public function next(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->assertInstallOpen();
        $step = $this->normalizeStep((string)($args['step'] ?? ''));
        $current = $this->getCurrentStep($request);
        $stepIndex = $this->stepIndex($step);

        if ($stepIndex > $current) {
            return $this->redirect($response, $this->stepPath($this->stepName($current)));
        }

        if ($step === 'environment') {
            $lang = $this->resolveLang($request);
            App::di()->set('lang', $lang);
            if (!$this->environmentStatus()['passed']) {
                throw new ExceptionBusiness(__('step.environment_fail', 'install'));
            }
        }

        $nextIndex = min(count(self::STEP_ORDER), $stepIndex + 1);
        $nextStep = $this->stepName($nextIndex);
        $response = $this->redirect($response, $this->stepPath($nextStep));

        $response = $this->withCookie($response, self::STEP_COOKIE, (string)$nextIndex);
        $response = $this->withCookie($response, self::LANG_COOKIE, $this->resolveLang($request));
        return $response;
    }

    #[Route(methods: 'GET', route: '/back/{step}')]
    public function back(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->assertInstallOpen();
        $step = $this->normalizeStep((string)($args['step'] ?? ''));
        $prevIndex = max(1, $this->stepIndex($step) - 1);
        $response = $this->redirect($response, $this->stepPath($this->stepName($prevIndex)));
        $response = $this->withCookie($response, self::LANG_COOKIE, $this->resolveLang($request));
        return $response;
    }

    #[Route(methods: 'GET', route: '/lang/{lang}')]
    public function lang(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $lang = $this->normalizeLang((string)($args['lang'] ?? ''));
        $current = $this->getCurrentStep($request);
        $redirect = (string)($request->getQueryParams()['redirect'] ?? '');
        if (!preg_match('#^/install(?:/|$)#', $redirect)) {
            $redirect = $this->stepPath($this->stepName($current));
        }
        $response = $this->redirect($response, $redirect);
        return $this->withCookie($response, self::LANG_COOKIE, $lang);
    }

    #[Route(methods: 'POST', route: '/prepare')]
    public function prepare(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->assertInstallOpen();
        $this->assertInstallStep($request);

        $payload = $request->getParsedBody();
        if (!is_array($payload)) {
            throw new ExceptionBusiness(__('payload.invalid', 'install'));
        }

        $token = $this->service()->prepare($payload);
        return send($response, 'ok', [
            'token' => $token,
        ]);
    }

    #[Route(methods: 'GET', route: '/modules/list')]
    public function moduleList(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->assertInstallOpen();
        $this->assertStepReached($request, 'modules');

        $cloudKey = trim((string)$request->getHeaderLine('X-Cloud-Key'));
        $cloudServer = trim((string)$request->getHeaderLine('X-Cloud-Server'));
        try {
            $list = $this->service()->fetchCloudModules($cloudKey, $cloudServer);
        } catch (\Throwable $e) {
            throw new ExceptionBusiness($e->getMessage(), 500);
        }
        return send($response, 'ok', $list);
    }

    #[Route(methods: 'POST', route: '/modules/apply')]
    public function moduleApply(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->assertInstallOpen();
        $this->assertStepReached($request, 'modules');

        $payload = $request->getParsedBody();
        if (!is_array($payload)) {
            throw new ExceptionBusiness(__('payload.invalid', 'install'));
        }

        $modules = $payload['modules'] ?? [];
        if (!is_array($modules)) {
            throw new ExceptionBusiness(__('payload.invalid', 'install'));
        }
        $installedModules = $payload['installed_modules'] ?? [];
        if (!is_array($installedModules)) {
            throw new ExceptionBusiness(__('payload.invalid', 'install'));
        }
        $upgradeInstalled = (bool)($payload['upgrade_installed'] ?? false);

        $cloudKey = trim((string)$request->getHeaderLine('X-Cloud-Key'));
        $cloudServer = trim((string)$request->getHeaderLine('X-Cloud-Server'));
        try {
            $result = $this->service()->installCloudModules($modules, $cloudKey, $upgradeInstalled, $installedModules, $cloudServer);
        } catch (\Throwable $e) {
            throw new ExceptionBusiness($e->getMessage(), 500);
        }
        return send($response, 'ok', $result);
    }

    #[Route(methods: 'GET', route: '/stream/{token}')]
    public function stream(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->assertInstallStep($request);
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('max_execution_time', '0');

        $response = $this->createSseResponse($response);
        $body = $response->getBody();

        $token = (string)($args['token'] ?? '');
        $lockHandle = null;
        $tokenLoaded = false;
        $output = null;

        try {
            $this->assertInstallOpen();
            $this->service()->getPendingToken($token);
            $tokenLoaded = true;

            $lockHandle = $this->service()->acquireRunningLock();
            if (!$lockHandle) {
                throw new ExceptionBusiness(__('process.running', 'install'), 409);
            }

            $output = $this->createCommandOutput($body);
            $this->runInstallStage(
                $body,
                $output,
                'composer update',
                '开始执行 composer update',
                fn () => $this->service()->runComposerUpdate($output)
            );
            $this->runInstallStage(
                $body,
                $output,
                'db:sync',
                '开始同步数据库',
                fn () => $this->service()->syncDatabase($output)
            );
            $this->runInstallStage(
                $body,
                $output,
                'menu:sync',
                '开始同步菜单',
                fn () => $this->service()->syncMenus($output)
            );

            $this->service()->markInstalled();
            $output->writeln('[done] 安装完成');
            $output->flush();

            $this->pushEvent($body, 'complete', [
                'message' => 'Install completed',
                'links' => [
                    'manage' => '/manage/',
                    'home' => '/',
                ],
            ]);
        } catch (\Throwable $e) {
            if ($output instanceof SseCommandOutput) {
                $output->writeln('[error] 安装失败: ' . $e->getMessage());
                $output->flush();
            }
            $this->pushEvent($body, 'error', [
                'message' => $e->getMessage(),
            ]);
        } finally {
            if ($tokenLoaded) {
                $this->service()->deletePendingToken($token);
            }
            $this->service()->releaseRunningLock($lockHandle);
        }

        return $response;
    }

    private function createCommandOutput(StreamInterface $body): SseCommandOutput
    {
        return new SseCommandOutput(function (string $line) use ($body): void {
            $this->pushEvent($body, 'log', ['message' => $line]);
        });
    }

    private function runInstallStage(
        StreamInterface $body,
        SseCommandOutput $output,
        string $name,
        string $message,
        callable $runner
    ): void {
        $this->pushEvent($body, 'stage', [
            'name' => $name,
            'message' => $message,
        ]);
        $output->writeln('[step] ' . $name);
        try {
            $runner();
            $output->writeln('[done] ' . $name);
        } catch (\Throwable $e) {
            $output->writeln('[error] ' . $name . ': ' . $e->getMessage());
            $output->flush();
            throw $e;
        }
        $output->flush();
    }

    private function renderStep(ServerRequestInterface $request, ResponseInterface $response, string $step): ResponseInterface
    {
        if ($this->service()->isInstalled()) {
            return $this->redirect($response, '/');
        }

        $step = $this->normalizeStep($step);
        $lang = $this->resolveLang($request);
        App::di()->set('lang', $lang);

        $current = $this->getCurrentStep($request);
        $target = $this->stepIndex($step);
        if ($target > $current) {
            return $this->redirect($response, $this->stepPath($this->stepName($current)));
        }

        $env = $this->environmentStatus();
        $i18n = [
            'fill_system' => __('js.fill_system', 'install'),
            'fill_db' => __('js.fill_db', 'install'),
            'fill_db_conn' => __('js.fill_db_conn', 'install'),
            'cloud_key_required_modules' => __('js.cloud_key_required_modules', 'install'),
            'modules_load_failed' => __('js.modules_load_failed', 'install'),
            'modules_apply_failed' => __('js.modules_apply_failed', 'install'),
            'modules_loading' => __('js.modules_loading', 'install'),
            'modules_installing' => __('js.modules_installing', 'install'),
            'confirm_reset' => __('js.confirm_reset', 'install'),
            'preparing' => __('js.preparing', 'install'),
            'prepare_failed' => __('js.prepare_failed', 'install'),
            'token_missing' => __('js.token_missing', 'install'),
            'wait_logs' => __('js.wait_logs', 'install'),
            'install_failed' => __('js.install_failed', 'install'),
            'connection_interrupted' => __('js.connection_interrupted', 'install'),
            'click_start' => __('js.click_start', 'install'),
            'modules_selected' => __('modules.selected', 'install'),
            'modules_server_timeout' => __('js.modules_server_timeout', 'install'),
        ];

        $rendered = sendTpl($response, dirname(__DIR__) . '/Views/wizard.latte', [
            'title' => __('main.title', 'install'),
            'step' => $step,
            'stepIndex' => $target,
            'stepOrder' => array_values(self::STEP_ORDER),
            'lang' => $lang,
            'langs' => self::SUPPORT_LANGS,
            'currentPath' => $request->getUri()->getPath(),
            'currentPathEncoded' => urlencode($request->getUri()->getPath()),
            'envGroups' => $env['groups'],
            'envPassed' => $env['passed'],
            'licenseContent' => $this->licenseContent(),
            'i18nJson' => json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $rendered = $this->withCookie($rendered, self::STEP_COOKIE, (string)$current);
        $rendered = $this->withCookie($rendered, self::LANG_COOKIE, $lang);
        return $rendered;
    }

    private function environmentStatus(): array
    {
        $extensions = [];
        $extensions[] = [
            'name' => __('env.php_version', 'install'),
            'current' => PHP_VERSION,
            'required' => '>= 8.4',
            'passed' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'blocking' => true,
        ];
        foreach (['pdo', 'openssl', 'mbstring', 'json', 'fileinfo', 'curl'] as $ext) {
            $extensions[] = [
                'name' => sprintf(__('env.extension', 'install'), $ext),
                'current' => extension_loaded($ext) ? __('env.installed', 'install') : __('env.missing', 'install'),
                'required' => __('env.required', 'install'),
                'passed' => extension_loaded($ext),
                'blocking' => true,
            ];
        }
        $pdoDriver = extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql') || extension_loaded('pdo_sqlite');
        $extensions[] = [
            'name' => __('env.pdo_driver', 'install'),
            'current' => $pdoDriver ? __('env.installed', 'install') : __('env.missing', 'install'),
            'required' => 'pdo_mysql | pdo_pgsql | pdo_sqlite',
            'passed' => $pdoDriver,
            'blocking' => true,
        ];
        $redisExt = extension_loaded('redis');
        $extensions[] = [
            'name' => sprintf(__('env.extension', 'install'), 'redis'),
            'current' => $redisExt ? __('env.installed', 'install') : __('env.missing', 'install'),
            'required' => __('env.optional', 'install'),
            'passed' => $redisExt,
            'blocking' => false,
        ];
        $imagickExt = extension_loaded('imagick');
        $extensions[] = [
            'name' => sprintf(__('env.extension', 'install'), 'imagick'),
            'current' => $imagickExt ? __('env.installed', 'install') : __('env.missing', 'install'),
            'required' => __('env.optional', 'install'),
            'passed' => $imagickExt,
            'blocking' => false,
        ];

        $functions = [];
        foreach (['proc_open', 'proc_get_status', 'proc_close'] as $func) {
            $functions[] = [
                'name' => sprintf(__('env.function', 'install'), $func),
                'current' => function_exists($func) ? __('env.installed', 'install') : __('env.missing', 'install'),
                'required' => __('env.required', 'install'),
                'passed' => function_exists($func),
                'blocking' => true,
            ];
        }
        foreach (['proc_terminate', 'exec'] as $func) {
            $functions[] = [
                'name' => sprintf(__('env.function', 'install'), $func),
                'current' => function_exists($func) ? __('env.installed', 'install') : __('env.missing', 'install'),
                'required' => __('env.optional', 'install'),
                'passed' => function_exists($func),
                'blocking' => false,
            ];
        }

        $permissions = [];
        foreach ([config_path(), data_path(), app_path()] as $path) {
            $permissions[] = [
                'name' => sprintf(__('env.path_writable', 'install'), str_replace(base_path(), '', $path)),
                'current' => is_writable($path) ? __('env.writable', 'install') : __('env.not_writable', 'install'),
                'required' => __('env.writable_required', 'install'),
                'passed' => is_writable($path),
                'blocking' => true,
            ];
        }

        $groups = [
            [
                'title' => __('env.group_extensions', 'install'),
                'rows' => $extensions,
            ],
            [
                'title' => __('env.group_functions', 'install'),
                'rows' => $functions,
            ],
            [
                'title' => __('env.group_permissions', 'install'),
                'rows' => $permissions,
            ],
        ];

        $passed = true;
        foreach ($groups as $group) {
            foreach ($group['rows'] as $item) {
                if ($item['blocking'] && !$item['passed']) {
                    $passed = false;
                    break 2;
                }
            }
        }

        return [
            'passed' => $passed,
            'groups' => $groups,
        ];
    }

    private function licenseContent(): string
    {
        $file = base_path('LICENSE');
        if (!is_file($file)) {
            return __('license.file_missing', 'install');
        }

        $content = file_get_contents($file);
        if ($content === false || trim($content) === '') {
            return __('license.file_missing', 'install');
        }

        return $content;
    }

    private function createSseResponse(ResponseInterface $response): ResponseInterface
    {
        $response = $response->withBody(new NonBufferedBody());
        return $response
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('X-Accel-Buffering', 'no');
    }

    private function pushEvent(StreamInterface $body, string $event, array $data): void
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '{}';
        }
        $body->write("event: {$event}\n");
        $body->write("data: {$payload}\n\n");
    }

    private function assertInstallOpen(): void
    {
        if ($this->service()->isInstalled()) {
            throw new ExceptionBusiness(__('main.installed', 'install'), 409);
        }
    }

    private function assertInstallStep(ServerRequestInterface $request): void
    {
        $this->assertStepReached($request, 'run');
    }

    private function assertStepReached(ServerRequestInterface $request, string $step): void
    {
        if ($this->getCurrentStep($request) < $this->stepIndex($step)) {
            throw new ExceptionBusiness(__('step.first_complete', 'install'), 409);
        }
    }

    private function getCurrentStep(ServerRequestInterface $request): int
    {
        $current = (int)($request->getCookieParams()[self::STEP_COOKIE] ?? 1);
        if ($current < 1) {
            $current = 1;
        }
        if ($current > count(self::STEP_ORDER)) {
            $current = count(self::STEP_ORDER);
        }
        return $current;
    }

    private function resolveLang(ServerRequestInterface $request): string
    {
        $lang = (string)($request->getQueryParams()['lang'] ?? '');
        if (!$lang) {
            $lang = (string)($request->getCookieParams()[self::LANG_COOKIE] ?? '');
        }
        if (!$lang) {
            $lang = (string)$request->getAttribute('lang', 'en-US');
        }
        return $this->normalizeLang($lang);
    }

    private function normalizeLang(string $lang): string
    {
        foreach (self::SUPPORT_LANGS as $item) {
            if (strcasecmp($item, $lang) === 0) {
                return $item;
            }
        }
        return 'zh-CN';
    }

    private function normalizeStep(string $step): string
    {
        foreach (self::STEP_ORDER as $name) {
            if ($name === $step) {
                return $step;
            }
        }
        throw new ExceptionBusiness(__('step.invalid', 'install'), 400);
    }

    private function stepIndex(string $step): int
    {
        $index = array_search($step, self::STEP_ORDER, true);
        if ($index === false) {
            throw new ExceptionBusiness(__('step.invalid', 'install'), 400);
        }
        return (int)$index;
    }

    private function stepName(int $index): string
    {
        if (!isset(self::STEP_ORDER[$index])) {
            return self::STEP_ORDER[1];
        }
        return self::STEP_ORDER[$index];
    }

    private function stepPath(string $step): string
    {
        return self::STEP_PATHS[$step] ?? self::STEP_PATHS['license'];
    }

    private function withCookie(ResponseInterface $response, string $name, string $value): ResponseInterface
    {
        $cookie = sprintf(
            '%s=%s; Path=/; Max-Age=%d; SameSite=Lax',
            rawurlencode($name),
            rawurlencode($value),
            self::COOKIE_AGE
        );
        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    private function redirect(ResponseInterface $response, string $path): ResponseInterface
    {
        return $response->withStatus(302)->withHeader('Location', $path);
    }

    private function service(): InstallService
    {
        if (!$this->service) {
            $this->service = new InstallService();
        }
        return $this->service;
    }
}
