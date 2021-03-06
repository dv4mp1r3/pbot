<?php

declare(strict_types=1);

namespace pbot\Misc;

use pbot\Bots\IBot;

class Application
{

    /**
     * @var bool
     */
    protected bool $isDebug = false;

    /**
     * @var IBot
     */
    protected IBot $bot;

    protected Logger $logger;

    /**
     * Application constructor.
     * @param IBot $bot
     * @param bool $isDebug
     * @param Logger $l
     */
    public function __construct(IBot $bot, Logger $l, bool $isDebug = false)
    {
        $this->bot = $bot;
        $this->isDebug = $isDebug;
        $this->logger = $l;
        set_error_handler([$l, Logger::ERROR_HANDLER_FUNCTION]);
    }

    /**
     * @throws \Exception
     */
    public function run() : void
    {
        try {
            $this->bot->execute();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}