<?php

namespace Warren\PSR;

use Psr\Http\Message\RequestInterface;

// RabbitMQ messages aren't _quite_ like HTTP messages, but there's
// enough parallels that we can shoehorn one into a PSR7 RequestInterface
// object just fine
class RabbitMQRequest implements RequestInterface
{
    private $version = "0-9-1";

    public function getProtocolVersion()
    {
        return $this->version;
    }

    public function withProtocolVersion($version)
    {
        $req = new RabbitMQRequest;
        $req->version = $version;
        return $req;
    }
}
