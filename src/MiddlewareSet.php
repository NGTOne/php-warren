<?php

namespace Warren;

use Warren\Error\NoMiddlewares;

class MiddlewareSet
{
    private $middlewares = [];

    public function addMiddleware(callable $ware) : MiddlewareSet
    {
        $this->middlewares[] = $ware;
        return $this;
    }

    public function getMiddlewareStack()
    {
        $wares = $this->middlewares;

        if (!count($wares)) {
            throw new NoMiddlewares;
        }

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
