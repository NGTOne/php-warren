<?php

namespace Warren\PSR;

use Psr\Http\Message\RequestInterface;

// RabbitMQ messages aren't _quite_ like HTTP messages, but there's
// enough parallels that we can shoehorn one into a PSR7 RequestInterface
// object just fine
class RabbitMQRequest implements RequestInterface
{
    private $version = "0-9-1";
    private $headers = [];
    private $headerNames = [];

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

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($name)
    {
        return isset($this->headerNames[strtolower($name)]) ?
            $this->headers[$this->headerNames[strtolower($name)]] : [];
    }

    public function withHeader($name, $value)
    {
        $req = clone $this;
        $req->headerNames[strtolower($name)] = $name;
        $req->headers[$name] = $value;
        return $req;
    }
}
