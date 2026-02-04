<?php

declare(strict_types=1);

namespace App\System\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ManageEvent extends Event
{
    /**
     * @param array<int,array<string,mixed>> $manages
     */
    public function __construct(private array $manages = [])
    {
    }

    public function setManages(array $manages): void
    {
        $this->manages = $manages;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getManages(): array
    {
        return $this->manages;
    }

    public function addManage(array $manage): void
    {
        $this->manages[] = $manage;
    }
}
