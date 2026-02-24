<?php

declare(strict_types=1);

namespace App\Install\Service;

use Symfony\Component\Console\Output\Output;

class SseCommandOutput extends Output
{
    private string $buffer = '';

    /**
     * @var callable
     */
    private $logger;

    /**
     * @param callable $logger
     */
    public function __construct(callable $logger)
    {
        parent::__construct(self::VERBOSITY_NORMAL, false);
        $this->logger = $logger;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message;
        if ($newline) {
            $this->buffer .= "\n";
        }
        $this->flushLines();
    }

    public function flush(): void
    {
        $line = trim(str_replace(["\r\n", "\r"], "\n", $this->buffer));
        $this->buffer = '';
        if ($line !== '') {
            ($this->logger)($line);
        }
    }

    private function flushLines(): void
    {
        $this->buffer = str_replace(["\r\n", "\r"], "\n", $this->buffer);
        while (($position = strpos($this->buffer, "\n")) !== false) {
            $line = trim(substr($this->buffer, 0, $position));
            $this->buffer = substr($this->buffer, $position + 1);
            if ($line !== '') {
                ($this->logger)($line);
            }
        }
    }
}
