<?php

declare(strict_types=1);

namespace App\System\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ManageConfigEvent extends Event
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config = [])
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }
}
