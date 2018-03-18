<?php

namespace Warren\PSR;

use Psr\Http\Message\MessageInterface;

// RabbitMQ messages aren't _quite_ like HTTP messages, but there's
// enough parallels that we can shoehorn one into a PSR7 MessageInterface
// object just fine
abstract class RabbitMQMessage implements MessageInterface
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

    private function headerVal($name)
    {
        return $this->headers[$this->headerNames[strtolower($name)]];
    }

    public function getHeader($name)
    {
        return $this->hasHeader($name) ? $this->headerVal($name) : [];
    }

    public function getHeaderLine($name)
    {
        if (!$this->hasHeader($name)) {
            return "";
        }

        $header = $this->headerVal($name);
        return is_array($header) ? implode($header, ", ") : $header;
    }

    public function hasHeader($name)
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function withHeader($name, $value)
    {
        $req = $this->withoutHeader($name);
        $req->headerNames[strtolower($name)] = $name;
        $req->headers[$name] = $value;
        return $req;
    }

    public function withAddedHeader($name, $value)
    {
        $header = $this->getHeader($name);

        if (is_string($header)) {
            $header = [$header];
        }

        if (is_array($value)) {
            $header = array_merge($header, $value);
        } else {
            $header[] = $value;
        }

        return $this->withHeader($name, $header);
    }

    public function withoutHeader($name)
    {
        $req = clone $this;
        if ($req->hasHeader($name)) {
            unset($req->headers[$req->headerNames[strtolower($name)]]);
            unset($req->headerNames[strtolower($name)]);
        }
        return $req;
    }

    public function getBody()
    {

    }

    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {

    }
}
