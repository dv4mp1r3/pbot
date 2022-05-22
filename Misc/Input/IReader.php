<?php

declare(strict_types=1);

namespace pbot\Misc\Input;

interface IReader
{
    public function readAll() : string;
}