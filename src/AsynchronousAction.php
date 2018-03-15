<?php

namespace Warren;

use Psr\Http\Message\RequestInterface;

interface AsynchronousAction
{
    public function performAsyncAction(
        RequestInterface $msg
    ) : void;
}
