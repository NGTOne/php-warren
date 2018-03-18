<?php

namespace Warren\PSR;

use GuzzleHttp\Psr7\Response;

class RabbitMQResponse extends Response
{
    use RabbitMQMessageTrait;

    public function __construct(
        $headers = [],
        $body = null,
        $version = '0-9-1'
    ) {
        parent::__construct(200, $headers, $body, $version);
    }
}
