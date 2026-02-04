<?php

declare(strict_types=1);

namespace App\System\Queue;

use Core\App;

class PingJob
{
    public function __invoke(string $work = ''): void
    {
        App::log('ping')->info('queue.ping', [
            'work' => $work,
            'time' => date('Y-m-d H:i:s'),
        ]);
    }
}
