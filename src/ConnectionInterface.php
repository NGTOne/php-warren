<?php

namespace Warren;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ConnectionInterface
{
    public function listen() : void;
    public function setCallback(callable $callback) : void;
    public function acknowledgeMessage($msg) : void;

    public function convertMessage($msg) : RequestInterface;
    public function sendMessage(
        ResponseInterface $response,
        $originalMsg,
        string $replyTo
    ) : void;
}
