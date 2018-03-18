<?php

namespace Warren\Test;

use PHPUnit\Framework\TestCase;

use Warren\PSR\RabbitMQRequest;

class RabbitMQRequestTest extends TestCase
{
    public function setUp() : void
    {
        $this->req = new RabbitMQRequest;
    }

    public function testDefaultVersion()
    {
        $result = $this->req->getProtocolVersion();
        $this->assertEquals("0-9-1", $result);
    }

    /**
     * @dataProvider withVersionProvider
     */
    public function testWithVersion($version)
    {
        $result = $this->req->withProtocolVersion($version);

        $this->assertEquals($version, $result->getProtocolVersion());
        $this->assertNotSame($this->req, $result);
    }

    public function withVersionProvider()
    {
        return [
            ["f00b4r"],
            ["0.9.1"],
            ["1.0.0"]
        ];
    }

    /**
     * @dataProvider getHeadersProvider
     */
    public function testGetHeaders($headers)
    {
        foreach ($headers as $key => $header) {
            $this->req = $this->req->withHeader($key, $header);
        }

        $this->assertEquals($headers, $this->req->getHeaders());
    }

    public function getHeadersProvider()
    {
        return [
            [
                ["foo" => "bar"]
            ], [
                ["foo" => "bar", "baz" => ["qux", "quux"]]
            ], [
                []
            ]
        ];
    }

    /**
     * @dataProvider getHeaderProvider
     */
    public function testGetHeader($headers, $key, $expected)
    {
        foreach ($headers as $key => $header) {
            $this->req = $this->req->withHeader($key, $header);
        }

        $this->assertEquals($expected, $this->req->getHeader($key));
    }

    public function getHeaderProvider()
    {
        return [
            [
                ["foo" => "bar"],
                "foo",
                "bar"
            ], [
                ["foo" => "bar"],
                "fOO",
                "bar"
            ], [
                ["foo" => ["bar"]],
                "foo",
                ["bar"]
            ], [
                ["foo" => "bar"],
                "baz",
                []
            ]
        ];
    }
}
