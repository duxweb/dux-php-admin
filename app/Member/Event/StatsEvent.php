<?php

namespace App\Member\Event;

use Symfony\Contracts\EventDispatcher\Event;

class StatsEvent extends Event
{
    private array $maps = [];

    public function __construct(public int $userId)
    {
    }

    public function setMap(string $name, array $data): void
    {
        if (!$this->maps[$name]) {
            $this->maps[$name] = [];
        }
        $this->maps[$name] = [
            ...$this->maps[$name],
            ...$data,
        ];
    }

    public function getMap(): array
    {
        return $this->maps;
    }

}