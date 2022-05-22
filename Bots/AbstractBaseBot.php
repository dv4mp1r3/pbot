<?php

declare(strict_types=1);

namespace pbot\Bots;

use pbot\Commands\CommandListener;

/**
 * Базовый класс для ботов
 * @package Bots
 */
abstract class AbstractBaseBot implements IBot
{

    protected ?CommandListener $commandListener;

    /**
     * AbstractBaseBot constructor.
     * @param CommandListener|null $listener
     */
    public function __construct(CommandListener $listener = null)
    {
        $this->setCommandListener($listener);
    }

    /**
     * @param CommandListener|null $listener
     */
    public function setCommandListener(CommandListener $listener = null) : void
    {
        $this->commandListener = $listener;
    }
}