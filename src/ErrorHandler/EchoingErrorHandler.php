<?php

namespace Warren\ErrorHandler;

use Warren\ErrorHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Warren\PSR\RabbitMQResponse;

class EchoingErrorHandler implements ErrorHandler
{
    private $currentMsg;

    public function setCurrentMessage(RequestInterface $msg) : void
    {
        $this->currentMsg = $msg;
    }

    public function handle(\Throwable $error) : ResponseInterface
    {
        $corrID = "unknown";
        if (
            $this->currentMsg
            and $this->currentMsg->hasHeader('correlation_id')
        ) {
            $corrID = $this->currentMsg->getHeaderLine('correlation_id');
        }

        echo "[".microtime(true).", corr_id $corrID]: ".
            $error->getMessage();

        // TODO: Make this a little smarter
        return new RabbitMQResponse;
    }
}
