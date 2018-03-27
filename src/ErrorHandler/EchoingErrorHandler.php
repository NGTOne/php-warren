<?php

namespace Warren\ErrorHandler;

use Warren\ErrorHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class EchoingErrorHandler implements ErrorHandler
{
    private $currentMsg;

    public function setCurrentMessage(RequestInterface $msg) : void
    {
        $this->currentMsg = $msg;
    }

    public function handle(\Throwable $error) : ResponseInterface
    {
        echo "[".microtime(true).", corr_id ".
            $this->currentMsg->getHeader("correlation_id").
            "]: ".$error->getMessage();

        // TODO: Make this a little smarter
        return new RabbitMQResponse;
    }
}
