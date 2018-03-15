<?php

namespace Warren\MessageProcessor;

use Warren\MiddlewareSet;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class MessageProcessor
{
    private $middlewares;

    public function __construct(MiddlewareSet $wares)
    {
        $this->middlewares = $wares;
        $this->middlewares->addMiddleware($this->finalMiddlewareLayer());
    }

    abstract protected function finalMiddlewareLayer() : callable;

    public function processMessage(
        RequestInterface $req
    ) : ResponseInterface {
        foreach ($this->middlewares as $ware) {

        }
    }
}
