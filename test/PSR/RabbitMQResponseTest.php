<?php

namespace Warren\Test;

use PHPUnit\Framework\TestCase;

use Warren\PSR\RabbitMQResponse;

class RabbitMQResponseTest extends TestCase
{
    public function setUp() : void
    {
        $this->req = new RabbitMQResponse;
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
}
