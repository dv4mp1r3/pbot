<?php

declare(strict_types=1);

namespace pbot\Bots;


interface ParentBot
{
    public function setParent(IBot $pb): void;
}