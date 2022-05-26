<?php

declare(strict_types=1);

namespace pbot\Bots;

use Bots\PbotException;
use pbot\Commands\CommandListener;
use pbot\Misc\Input\IReader;

/**
 * Базовый бот для обработки входящей инфы от телеграма
 */
class TelegramBot extends AbstractBaseBot
{
    const MESSAGE_TYPE_TEXT = 'sendMessage';
    const MESSAGE_TYPE_STICKER = 'sendSticker';

    const FUNCTION_GETFILE = 'getFile';

    const API_URL = 'https://api.telegram.org';

    const CONTENT_TYPE = 'Content-Type: application/json';

    protected array $decodedInput = [];

    protected string $rawText = '';

    protected int $chatId;

    /**
     * Устанавливается в true после первой выполненной команды
     * @see execute
     * @var bool
     */
    protected bool $isCommandAlreadyExecuted = false;

    /**
     * TelegramBot constructor.
     * @param IReader $reader
     * @param CommandListener|null $listener
     * @throws \Exception
     */
    public function __construct(IReader $reader, CommandListener $listener = null)
    {
        if (!defined('IDENT')) {
            throw new PbotException('Constant IDENT is not defined');
        }
        $keyMessage = 'message';
        $this->decodedInput = json_decode($reader->readAll(), true);
        if (!is_array($this->decodedInput) || !isset($this->decodedInput[$keyMessage]['chat']['id'])) {
            throw new PbotException('Bad data format');
        }

        $this->rawText = $this->parseRawText($this->decodedInput[$keyMessage]);

        $this->chatId = $this->decodedInput[$keyMessage]['chat']['id'];
        parent::__construct($listener);
    }

    protected function parseRawText($message): string
    {
        $keys = ['text', 'caption'];
        foreach ($keys as $key) {
            if (array_key_exists($key, $message)) {
                return $message[$key];
            }
        }
        return '';
    }

    /**
     * Попытка обработки зарегистрированных команд
     * @return mixed
     * @throws \Exception
     * @see registerCommand
     */
    public function execute(): void
    {
        if (is_null($this->commandListener)) {
            return;
        }

        if ($this->commandListener->isCommand($this->rawText)) {
            $this->isCommandAlreadyExecuted = true;
            $result = $this->commandListener->executeFoundCommand();
            $this->sendMessage($this->chatId, $result);
        }
    }

    /**
     *
     * @param int $chatId
     * @param string $text
     * @param string $method
     * @throws \InvalidArgumentException
     */
    protected function sendMessage(int $chatId, string $text, string $method = 'sendMessage')
    {
        if (php_sapi_name() !== 'cli') {
            header(CONTENT_TYPE);
        }
        $reply['method'] = $method;
        $reply['chat_id'] = $chatId;
        switch ($method) {
            case TelegramBot::MESSAGE_TYPE_TEXT:
                $reply['text'] = $text;
                break;
            case TelegramBot::MESSAGE_TYPE_STICKER:
                $reply['sticker'] = $text;
                break;
            default:
                throw new \InvalidArgumentException("Invalid method value: $method");
        }
        echo json_encode($reply);
    }

    /**
     * @param string $stringOutput
     * @return array
     * @throws \Exception
     */
    protected function checkTelegramOutput(string $stringOutput): array
    {
        $data = json_decode($stringOutput, true);
        if (json_last_error() > 0) {
            throw new PbotException(json_last_error_msg());
        }
        if ($data['ok'] !== true) {
            throw new PbotException("error {$data['error_code']}: {$data['description']}");
        }
        return $data;
    }

    /**
     * @param string $url
     * @return resource
     * @throws \Exception
     */
    protected function buildCurlGetTemplate(string $url)
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new PbotException("Error on curl_init");
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', CONTENT_TYPE]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        return $ch;
    }

    /**
     * @param string $id
     * @return string
     * @throws \Exception
     */
    protected function getFilePath(string $id): string
    {
        $url = self::buildFunctionUrl('getFile', ['file_id' => $id]);
        $ch = $this->buildCurlGetTemplate($url);
        $fileData = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new PbotException(curl_error($ch));
        }
        curl_close($ch);
        $fileData = $this->checkTelegramOutput($fileData);
        return $fileData['result']['file_path'];
    }

    /**
     * @param int $chatId
     * @param string $fileContent
     * @return array
     * @throws \Exception
     */
    protected function sendPhoto(int $chatId, string $fileContent): array
    {
        $boundary = uniqid();
        $eol = "\r\n";
        $name = 'photo';
        $delimiter = '-------------' . $boundary;
        $url = self::buildFunctionUrl(__FUNCTION__, ['chat_id' => $chatId]);
        $data = "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
            . 'Content-Type: image/jpeg' . $eol;
        $data .= $eol;
        $data .= $fileContent . $eol;
        $data .= "--" . $delimiter . "--" . $eol;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            [
                "Content-Type: multipart/form-data; boundary=$delimiter",
                "Content-Length: " . strlen($data)
            ]
        );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new PbotException(curl_error($ch));
        }
        curl_close($ch);
        return $this->checkTelegramOutput($output);
    }

    /**
     * @param $filePath
     * @return string
     * @throws \Exception
     */
    protected function downloadFile(string $filePath): string
    {
        if (!defined('TELEGRAM_BOT_TOKEN')) {
            throw new PbotException('constant TELEGRAM_BOT_TOKEN is not defined');
        }

        $apiUrl = self::API_URL . '/file/bot' . TELEGRAM_BOT_TOKEN . '/' . $filePath;
        $ch = $this->buildCurlGetTemplate($apiUrl);
        $image = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new PbotException(curl_error($ch));
        }

        curl_close($ch);
        return $image;
    }

    /**
     * @param string $function имя функции апи
     * @param array $params массив параметров (ключ-значение)
     * @return string
     * @throws \Exception
     */
    protected static function buildFunctionUrl(string $function, array $params = []): string
    {
        if (!defined('TELEGRAM_BOT_TOKEN')) {
            throw new PbotException('constant TELEGRAM_BOT_TOKEN is not defined');
        }

        $apiUrl = self::API_URL . '/bot' . TELEGRAM_BOT_TOKEN . '/' . $function;
        if (count($params) > 0) {
            $apiUrl .= '?' . http_build_query($params);
        }
        return $apiUrl;
    }

    /**
     *
     * @param array $message
     * @param string $ident
     * @return boolean
     */
    public function isReply(array $message, string $ident = IDENT): bool
    {
        $keyReplyTo = 'reply_to_message';
        $keyMessage = 'message';
        if (!$this->tryValidateMessage($message, $keyMessage, $keyReplyTo)) {
            return false;
        }

        if (mb_strlen($ident) > 0) {
            return $message[$keyMessage][$keyReplyTo]['from']['username'] === $ident;
        }
        return true;
    }

    /**
     * @param array $message
     * @param string $keyMessage
     * @param string $keyReplyTo
     * @return bool
     */
    private function tryValidateMessage(array $message, string $keyMessage, string $keyReplyTo): bool {
        if (empty($message[$keyMessage][$keyReplyTo])) {
            return false;
        }

        if (empty($message[$keyMessage][$keyReplyTo]['from'])) {
            return false;
        }

        return true;
    }
}
