<?php

namespace App\Member\Event;

use App\Member\Interface\CollectInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CollectEvent extends Event
{
    public array $maps = [];

    public function __construct()
    {
    }

    public function setMap(string $label, string $name, string $type, ?CollectInterface $collect = null): void
    {
        $this->maps[$name] = [
            'label' => $label,
            "class" => $type,
            "collect" => $collect
        ];
    }

    public function getMapName(string $class): string|null
    {
        foreach ($this->maps as $name => $vo) {
            if ($vo['class'] == $class) {
                return $name;
            }
        }
        return '';
    }

    public function getMapType(string $name): array|null
    {
        return $this->maps[$name];
    }

    public function getName(string $class): string|null
    {
        foreach ($this->maps as $name => $vo) {
            if ($vo['class'] == $class) {
                return $name;
            }
        }
        return '';
    }

}