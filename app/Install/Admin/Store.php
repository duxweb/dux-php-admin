<?php

declare(strict_types=1);

namespace App\Install\Admin;

use App\Install\Service\CloudModuleService;
use App\Install\Service\SseCommandOutput;
use Core\Handlers\ExceptionBusiness;
use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\NonBufferedBody;

#[Resource(app: 'admin', route: '/install/store', name: 'install.store', actions: false)]
class Store
{
    private ?CloudModuleService $service = null;

    #[Action(methods: 'GET', route: '', name: 'list')]
    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cloudKey = trim((string)$request->getHeaderLine('X-Cloud-Key'));
        $query = $request->getQueryParams();
        $cloudServer = trim((string)($query['cloud_server'] ?? ''));
        $tab = trim((string)($query['tab'] ?? 'all'));

        try {
            $data = $this->service()->listModules($cloudKey, $cloudServer);
        } catch (\Throwable $e) {
            $this->throwBusiness($e);
        }

        $list = (array)($data['list'] ?? []);
        if ($tab === 'installed') {
            $list = array_values(array_filter($list, function (array $item): bool {
                return (bool)($item['installed'] ?? false);
            }));
        }

        return send($response, 'ok', $list, [
            'enabled' => (bool)($data['enabled'] ?? false),
            'server' => (string)($data['server'] ?? ''),
            'servers' => (array)($data['servers'] ?? []),
        ]);
    }

    #[Action(methods: 'GET', route: '/detail/{id}', name: 'detail')]
    public function detail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cloudKey = trim((string)$request->getHeaderLine('X-Cloud-Key'));
        $cloudServer = trim((string)($request->getQueryParams()['cloud_server'] ?? ''));
        $id = trim((string)($args['id'] ?? ''));

        try {
            $data = $this->service()->moduleDetail($id, $cloudKey, $cloudServer);
        } catch (\Throwable $e) {
            $this->throwBusiness($e);
        }

        return send($response, 'ok', $data);
    }

    #[Action(methods: 'POST', route: '/action/prepare', name: 'actionPrepare')]
    public function actionPrepare(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $payload = $request->getParsedBody();
        if (!is_array($payload)) {
            throw new ExceptionBusiness('Invalid payload', 400);
        }

        $cloudKey = trim((string)$request->getHeaderLine('X-Cloud-Key'));
        $cloudServer = trim((string)($payload['cloud_server'] ?? ''));

        try {
            $token = $this->service()->prepareStoreAction($payload, $cloudKey, $cloudServer);
        } catch (\Throwable $e) {
            $this->throwBusiness($e);
        }

        return send($response, 'ok', [
            'token' => $token,
        ]);
    }

    #[Action(methods: 'GET', route: '/action/stream/{token}', name: 'actionStream')]
    public function actionStream(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
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
            $this->service()->getStoreToken($token);
            $tokenLoaded = true;

            $lockHandle = $this->service()->acquireStoreRunningLock();
            if (!$lockHandle) {
                throw new ExceptionBusiness('Another store task is running', 409);
            }

            $output = new SseCommandOutput(function (string $line) use ($body): void {
                $this->pushEvent($body, 'log', ['message' => $line]);
            });

            $result = $this->service()->runStoreActionToken($token, $output);
            $output->writeln('[done] store action completed');
            $output->flush();

            $this->pushEvent($body, 'complete', [
                'message' => 'Store action completed',
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            if ($output instanceof SseCommandOutput) {
                $output->writeln('[error] ' . $e->getMessage());
                $output->flush();
            }
            $this->pushEvent($body, 'error', [
                'message' => $e->getMessage(),
            ]);
        } finally {
            if ($tokenLoaded) {
                $this->service()->deleteStoreToken($token);
            }
            $this->service()->releaseStoreRunningLock($lockHandle);
        }

        return $response;
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

    private function throwBusiness(\Throwable $e): never
    {
        if ($e instanceof ExceptionBusiness) {
            throw $e;
        }
        throw new ExceptionBusiness($e->getMessage(), 500);
    }

    private function service(): CloudModuleService
    {
        if (!$this->service) {
            $this->service = new CloudModuleService();
        }
        return $this->service;
    }
}
