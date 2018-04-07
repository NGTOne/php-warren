<?php

namespace Warren\Test\Stub;

use Warren\ConnectionInterface;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Warren\PSR\RabbitMQRequest;
use Warren\Signal\SignalHandler;

// Simple stub class that uses arrays as its "messages"
class StubConnection implements ConnectionInterface
{
    private $headers;
    private $msgToSend;
    private $callback;

    public $sentMessage;

    public function __construct(array $msgToSend, array $headers)
    {
        $this->msgToSend = $msgToSend;
        $this->headers = $headers;
    }

    public function listen(SignalHandler $handler) : void
    {
        call_user_func($this->callback, json_encode([
           'body' => $this->msgToSend,
           'header' => $this->headers
        ]));
    }

    public function setCallback(callable $callback) : void
    {
        $this->callback = $callback;
    }

    public function acknowledgeMessage($msg) : void
    {
        // No-op in a dummy class like this
    }

    public function convertMessage($msg) : RequestInterface
    {
        $msg = json_decode($msg, true);

        return new RabbitMQRequest(
            $msg['header'],
            json_encode($msg['body'])
        );
    }

    public function sendResponse(
        $originalMsg,
        ResponseInterface $response
    ) : void {
        $this->sentMessage = $response;
    }
}
