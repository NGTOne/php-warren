<?php

namespace Warren;

class MiddlewareSet implements \IteratorAggregate
{
    private $middlewares;

    public function addMiddleware(callable $ware)
    {
        $this->middlewares[] = $ware;
    }

    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->middlewares);
    }
}
