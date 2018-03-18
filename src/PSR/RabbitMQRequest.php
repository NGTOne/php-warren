<?php

namespace Warren\PSR;

use Psr\Http\Message\RequestInterface;

class RabbitMQRequest extends RabbitMQMessage implements RequestInterface
{
    public function getRequestTarget()
    {

    }

    public function withRequestTarget($requestTarget)
    {

    }

    public function getMethod()
    {

    }

    public function withMethod($method)
    {

    }

    public function getUri()
    {

    }

    public function withUri(
        \Psr\Http\Message\UriInterface $uri,
        $preserveHost = false
    ) {

    }
}
