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

    public function getMiddlewareStack()
    {
        $wares = $this->middlewares;

        $func = function($req, $res) use ($wares) {
            return call_user_func($wares[0], $req, $res);
        };

        array_shift($wares);

        foreach ($wares as $ware) {
            $func = function($req, $res) use ($ware, $func) {
                return call_user_func($ware, $req, $res, $func);
            };
        }

        return $func;
    }
}
