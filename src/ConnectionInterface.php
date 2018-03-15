<?php

namespace Warren;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ConnectionInterface
{
    public function connect() : void;

    public function convertMessage($msg) : RequestInterface;
    public function sendMessage(
        ResponseInterface $response,
        string $target
    ) : void;
}
