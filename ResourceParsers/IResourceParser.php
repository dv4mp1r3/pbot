<?php

declare(strict_types=1);

namespace pbot\ResourceParsers;

interface IResourceParser{

    public function parse() : array;
}