<?php

declare(strict_types=1);

namespace pbot\Bots\Events;

interface IEvent
{
    public function run();
}