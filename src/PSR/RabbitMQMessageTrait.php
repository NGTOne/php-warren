<?php

namespace Warren\PSR;

// RabbitMQ messages aren't _quite_ like HTTP messages, but there's
// enough parallels that we can shoehorn one into a PSR7 MessageInterface
// object just fine
trait RabbitMQMessageTrait
{
    private $version = "0-9-1";

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
