<?php

namespace Warren\ErrorHandler;

use Warren\ErrorHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RethrowingErrorHandler implements ErrorHandler
{
    public function setCurrentMessage(RequestInterface $msg) : void
    {
        // Since we're just re-throwing the error anyways, no need
        // to bother with this
    }

    public function handle(\Throwable $error) : ResponseInterface
    {
        throw $error;
    }
}
