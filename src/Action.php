<?php

namespace Warren;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Action
{
    public function performAction(
        RequestInterface $msg
    ) : ResponseInterface;
}
