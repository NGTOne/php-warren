<?php

namespace Warren;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface SynchronousAction
{
    public function performSynchronousAction(
        RequestInterface $msg
    ) : ResponseInterface;
}
