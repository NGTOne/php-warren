<?php

namespace Warren\Error;

class NoMiddlewares extends \Exception
{
    public function __construct()
    {
        parent::__construct("No middlewares have been provided");
    }
}
