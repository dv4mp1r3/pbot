<?php

declare(strict_types=1);

namespace pbot\tests\Bots;

use Exception;
use pbot\Bots\TelegramBot;
use pbot\Commands\CommandListener;
use pbot\Misc\Input\FileReader;
use pbot\tests\Commands\TestCommand;
use PHPUnit\Framework\TestCase;

final class TelegramBotTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testExecuteNoResponse(): void {
        $reader = new FileReader(__DIR__.'/../input/text_chat.json');
        $commandListener = new CommandListener();
        $commandListener->addCommand('/test', new TestCommand());
        $bot = new TelegramBot($reader, $commandListener);
        $bot->execute();
        $this->assertEquals("", $this->getActualOutput());
    }

    /**
     * @throws Exception
     */
    public function testExecuteTestCommand(): void {
        $reader = new FileReader(__DIR__.'/../input/text_chat_test_command.json');
        $commandListener = new CommandListener();
        $command = new TestCommand();
        $commandListener->addCommand('/test', $command);
        $bot = new TelegramBot($reader, $commandListener);
        $bot->execute();
        $output = $this->getActualOutput();
        $this->assertEquals('{"method":"sendMessage","chat_id":1111,"text":"123456"}', $output);
        $this->assertEquals(true, $command->alreadyExecuted());
    }
}