<?php

declare(strict_types=1);

namespace pbot\tests\Commands;

use pbot\Commands\ICommand;

class TestCommand implements ICommand
{
    protected bool $isExecuted = false;

    public function run(array $args, array $decodedInput = []): string
    {
        $this->isExecuted = true;
        return implode($args);
    }

    public function alreadyExecuted(): bool
    {
        return $this->isExecuted;
    }
}