<?php

namespace Warren;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ErrorHandler
{
    public function setCurrentMessage(RequestInterface $msg) : void;

    public function handle(\Throwable $error) : ResponseInterface;
}
