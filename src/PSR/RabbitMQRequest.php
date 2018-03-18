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

    public function getHeaderLine($name)
    {

    }

    public function hasHeader($name)
    {

    }

    public function withHeader($name, $value)
    {
        $req = clone $this;
        $req->headerNames[strtolower($name)] = $name;
        $req->headers[$name] = $value;
        return $req;
    }

    public function withAddedHeader($name, $value)
    {

    }

    public function withoutHeader($name)
    {

    }

    public function getBody()
    {

    }

    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {

    }

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
