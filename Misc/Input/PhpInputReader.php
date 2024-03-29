<?php

declare(strict_types=1);

namespace pbot\Misc\Input;

use pbot\Bots\PbotException;

class PhpInputReader implements IReader
{

    public function readAll(): string
    {
        $file = "php://input";
        $content = file_get_contents($file);
        if (empty($content)) {
            throw new PbotException("Error on file_get_contents for {$file}");
        }
        return $content;
    }
}