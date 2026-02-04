<?php

declare(strict_types=1);

namespace App\System\Scheduler;

use Core\App;
use Core\Scheduler\Attribute\Scheduler;

class PingScheduler
{
    #[Scheduler(name: 'ping', desc: '写入 ping 日志用于测试')]
    public function handle(array $params = []): void
    {
        App::log('ping')->info('ping', [
            'params' => $params,
            'time' => date('Y-m-d H:i:s'),
        ]);
    }
}
