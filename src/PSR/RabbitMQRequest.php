<?php

namespace Warren\PSR;

use GuzzleHttp\Psr7\Request;

class RabbitMQRequest extends Request
{
    use RabbitMQMessageTrait;

    public function __construct(
        $headers = [],
        $body = null,
        $version = '0-9-1'
    ) {
        parent::__construct("RabbitMQ", "/", $headers, $body, $version);
    }
}
