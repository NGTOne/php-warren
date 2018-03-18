<?php

namespace Warren\PSR;

use Psr\Http\Message\RequestInterface;

class RabbitMQRequest extends RabbitMQMessage implements RequestInterface
{
    public function __construct(
        $headers = [],
        $body = null,
        $version = '0-9-1'
    ) {
        parent::__construct("RabbitMQ", "/", $headers, $body, $version);
    }
}
