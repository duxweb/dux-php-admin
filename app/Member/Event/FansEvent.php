<?php

namespace App\Member\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FansEvent extends Event
{
    public array $maps = [];

    public function __construct()
    {
    }

    public function setMap(string $name,  ?callable $callback = null): void
    {
        $this->maps[$name] = [
            "callback" => $callback
        ];
    }


}