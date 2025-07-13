<?php

namespace App\Member\Event;

use App\Member\Interface\AssessInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AssessEvent extends Event
{

    public array $types = [];

    public array $sources = [];

    public function setType(string $label, string $name, string $class, ?AssessInterface $assess = null): void
    {
        $this->types[$name] = [
            'name' => $name,
            'label' => $label,
            'class' => $class,
            'assess' => $assess
        ];
    }

    public function getType(string $name): ?array
    {
        return $this->types[$name];
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param string $label
     * @param string $name
     * @param string $class
     * @return void
     */
    public function setSource(string $label, string $name, string $class): void
    {
        $this->types[$name] = [
            'name' => $name,
            'label' => $label,
            'class' => $class,
        ];
    }

    public function getSource(string $name): ?array
    {
        return $this->types[$name];
    }

    public function getSources(): array
    {
        return $this->types;
    }

}