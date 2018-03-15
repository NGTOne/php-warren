<?php

namespace Warren;

class MiddlewareSet implements \IteratorAggregate
{
    private $middlewares = [];

    public function addMiddleware(callable $ware) : MiddlewareSet
    {
        $this->middlewares[] = $ware;
        return $this;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->middlewares);
    }

    public function clone() : MiddlewareSet
    {
        return array_reduce($this->middlewares, function ($set, $ware) {
            return $set->addMiddleware($ware);
        }, new MiddlewareSet);
    }
}
