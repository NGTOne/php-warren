<?php

namespace Warren;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface AsynchronousAction
{
    public function performAsynchronousAction(
        RequestInterface $req,
        ResponseInterface $res
    ) : void;
}
