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
    public function testGetHeader($headers, $testKey, $expected)
    {
        foreach ($headers as $key => $header) {
            $this->req = $this->req->withHeader($key, $header);
        }

        $this->assertEquals($expected, $this->req->getHeader($testKey));
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

    /**
     * @dataProvider hasHeaderProvider
     */
    public function testHasHeader($headers, $testKey, $expected)
    {
        foreach ($headers as $key => $header) {
            $this->req = $this->req->withHeader($key, $header);
        }

        $this->assertEquals($expected, $this->req->hasHeader($testKey));
    }

    public function hasHeaderProvider()
    {
        return [
            [
                ["foo" => "bar"],
                "foo",
                true
            ], [
                ["foo" => "bar"],
                "fOO",
                true
            ], [
                ["foo" => ["bar"]],
                "foo",
                true
            ], [
                ["foo" => "bar"],
                "baz",
                false
            ]
        ];
    }

    /**
     * @dataProvider getHeaderLineProvider
     */
    public function testgetHeaderLine($headers, $testKey, $expected)
    {
        foreach ($headers as $key => $header) {
            $this->req = $this->req->withHeader($key, $header);
        }

        $this->assertEquals(
            $expected,
            $this->req->getHeaderLine($testKey)
        );
    }

    public function getHeaderLineProvider()
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
                "bar"
            ], [
                ["foo" => "bar"],
                "baz",
                ""
            ], [
                ["foo" => ["bar", "baz"]],
                "foo",
                "bar, baz"
            ]
        ];
    }

    /**
     * @dataProvider withHeaderProvider
     */
    public function testWithHeader($headers, $expected)
    {
        $result = $this->req;
        foreach ($headers as $key => $header) {
            $new = $result->withHeader($key, $header);
            $this->assertNotSame($result, $new);
            $result = $new;
        }

        $this->assertEquals($expected, $result->getHeaders());
    }

    public function withHeaderProvider()
    {
        return [
            [
                ["foo" => "bar"],
                ["foo" => "bar"]
            ], [
                ["FOO" => "bar"],
                ["FOO" => "bar"]
            ], [
                ["foo" => "bar", "FOO" => "baz"],
                ["FOO" => "baz"]
            ], [
                ["foo" => "bar", "bar" => "baz"],
                ["foo" => "bar", "bar" => "baz"]
            ], [
                ["foo" => ["bar", "quux"], "bar" => "baz"],
                ["foo" => ["bar", "quux"], "bar" => "baz"]
            ]
        ];
    }
}
