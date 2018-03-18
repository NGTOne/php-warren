<?php

namespace Warren\PSR;

use Psr\Http\Message\MessageInterface;
use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\Request;

// RabbitMQ messages aren't _quite_ like HTTP messages, but there's
// enough parallels that we can shoehorn one into a PSR7 MessageInterface
// object just fine
abstract class RabbitMQMessage extends Request implements MessageInterface
{
    private $version = "0-9-1";

    use MessageTrait;

    public function getProtocolVersion()
    {
        return $this->version;
    }

    public function withProtocolVersion($version)
    {
        $req = clone $this;
        $req->version = $version;
        return $req;
    }
}
