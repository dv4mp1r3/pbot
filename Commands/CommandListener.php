<?php

declare(strict_types=1);

namespace pbot\Commands;


class CommandListener
{
    protected array $registeredCommands = [];

    /**
     * @var ICommand
     */
    protected ICommand $foundCommand;

    /**
     * @var array
     */
    protected array $commandArgs;

    /**
     * @param string $commandText подстрока текста сообщения, которую воспринимать как команду
     * @param ICommand $command
     * @return CommandListener
     */
    public function addCommand(string $commandText, ICommand $command): CommandListener
    {
        $this->registeredCommands [$commandText] = $command;
        return $this;
    }

    /**
     * @param string $messageText
     * @return bool
     */
    public function isCommand(string $messageText): bool
    {
        foreach ($this->registeredCommands as $commandName => $commandObject) {
            if (mb_stripos($messageText, $commandName) === 0) {
                $this->foundCommand = $commandObject;
                $this->commandArgs = $this->parseCommandArgs($messageText, $commandName);
                return true;
            }
        }
        return false;
    }

    /**
     * парсинг аргументов команды из текста сообщения
     * @param string $messageText
     * @param string $commandName
     * @return array
     */
    public function parseCommandArgs(string $messageText, string $commandName): array
    {
        $argsString = mb_strcut($messageText, mb_strlen($commandName));
        return array_values(array_filter(mb_split(' ', $argsString)));
    }

    public function executeFoundCommand(): string
    {
        return $this->foundCommand->run($this->commandArgs);
    }

}