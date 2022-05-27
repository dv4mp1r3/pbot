<?php
declare(strict_types=1);

namespace pbot\Misc\Input;

use pbot\Bots\PbotException;

class FileReader implements IReader
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function readAll(): string
    {
        if (!file_exists($this->filePath)) {
            throw new PbotException("File {$this->filePath} doesn't exists");
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            throw new PbotException("Error on file_get_contents for {$this->filePath}");
        }

        return $content;
    }
}