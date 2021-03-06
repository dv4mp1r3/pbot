<?php

declare(strict_types=1);

namespace pbot\Bots;

use pbot\Bots\Events\IEvent;

class SocketBot implements IBot
{

    const BEFORE_SEND_EVENT = 'beforeSend';
    const AFTER_SEND_EVENT = 'afterSend';
    /**
     * @var string
     */
    protected string $server;

    /**
     * @var string
     */
    protected string $port;

    /**
     * @var resource
     */
    private $s;

    protected array $beforeSendEvents = [];

    protected array $afterSendEvents = [];

    /**
     * @param string $eventType
     * @param IEvent $event
     * @return SocketBot
     */
    public function setEvent(string $eventType, IEvent $event): SocketBot
    {
        switch ($eventType) {
            case self::BEFORE_SEND_EVENT:
                $this->beforeSendEvents[] = $event;
                break;
            case self::AFTER_SEND_EVENT:
                $this->afterSendEvents[] = $event;
                break;
            default:
                break;

        }
        return $this;
    }

    /**
     * SocketBot constructor.
     * @param string $server
     * @param string $port
     */
    public function __construct(string $server, string $port)
    {
        $this->server = $server;
        $this->port = $port;
    }

    public function execute(): void
    {
        $this->openConnection();

    }

    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * @throws \Exception
     */
    protected function openConnection(): void
    {
        $this->s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (socket_connect($this->s, $this->server, intval($this->port)) === false) {
            throw new PbotException("socket_connect() failed: "
                . socket_strerror(socket_last_error($this->s)));
        }
    }

    protected function closeConnection()
    {
        if ($this->s) {
            \socket_close($this->s);
        }
    }

    /**
     * @param string $function
     * @param int $returnValue
     * @param int $lastError
     */
    protected function debugPrintSocketError(string $function, int $returnValue, int $lastError)
    {
        if (defined('IS_DEBUG') && IS_DEBUG) {
            $ts = socket_strerror($lastError);
            if ($lastError === SOCKET_EAGAIN) {
                return;
            }
            echo "{$function}: {$ts} ($lastError)\n";
            if ($returnValue) {
                echo "return value $returnValue\n";
            }
        }
    }

    /**
     * @param string $string
     * @param bool $startEvents
     */
    protected function sendString(string $string, bool $startEvents = false)
    {
        SocketBot::runEvents($this->beforeSendEvents, $startEvents);

        $size = strlen($string);
        $i = \socket_write($this->s, $string, $size);
        $lastError = socket_last_error($this->s);
        $this->debugPrintSocketError(__FUNCTION__, (int)$i, $lastError);

        SocketBot::runEvents($this->afterSendEvents, $startEvents);
    }

    private static function runEvents(array $events, bool $startEvents): void
    {
        if (!$startEvents) {
            return;
        }
        foreach ($events as $event) {
            $event->run();
        }
    }

    /**
     * @param int $len
     * @param int $type
     * @return string
     * @throws \Exception
     */
    protected function receiveString(int $len, int $type): string
    {
        $buffer = '';
        $i = socket_recv($this->s, $buffer, $len, $type);
        $lastError = socket_last_error($this->s);
        $this->debugPrintSocketError(__FUNCTION__, (int)$i, $lastError);
        if ($lastError > 0 && $lastError !== SOCKET_EAGAIN) {
            throw new PbotException("socket_recv error " . socket_strerror($lastError) . " ($lastError)");
        }
        if ($i === 0 || !$i) {
            return '';
        }
        return $buffer;
    }

    protected function getConnectionLastErrorCode(): int
    {
        return socket_last_error($this->s);
    }

    protected function getConnectionLastError(): string
    {
        return socket_strerror($this->getConnectionLastErrorCode());
    }
}