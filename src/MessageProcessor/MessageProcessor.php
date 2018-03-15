<?php

namespace Warren\MessageProcessor;

use Warren\MiddlewareSet;

abstract class MessageProcessor
{
    private $middlewares;

    public function __construct(MiddlewareSet $wares)
    {
        $this->middlewares = $wares;
        $this->middlewares->addMiddleware($this->finalMiddlewareLayer());
    }

    abstract protected function finalMiddlewareLayer() : callable;
}
