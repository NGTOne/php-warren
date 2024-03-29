<?php

namespace Warren\MessageProcessor;

use Warren\MiddlewareSet;
use Warren\PSR\RabbitMQResponse;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class MessageProcessor
{
    private $middlewares;

    public function __construct(MiddlewareSet $wares)
    {
        $this->middlewares = clone $wares;
        $this->middlewares->addMiddleware($this->finalMiddlewareLayer());
    }

    abstract protected function finalMiddlewareLayer() : callable;

    public function processMessage(
        RequestInterface $req
    ) : ResponseInterface {
        $res = new RabbitMQResponse();

        return $this->middlewares->getMiddlewareStack()($req, $res);
    }
}
