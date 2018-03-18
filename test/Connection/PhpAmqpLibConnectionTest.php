<?php

namespace Warren\Test\Connection;

use PHPUnit\Framework\TestCase;
use Warren\Connection\PhpAmqpLibConnection;

use Warren\Test\Stub\StubAMQPChannel;

class PhpAmqpLibConnectionTest extends TestCase
{
    public function setUp() : void
    {
        $this->channel = $this->getMockBuilder(StubAMQPChannel::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'basic_ack',
                'basic_consume',
                'basic_publish'
            ])->getMock();

        $this->conn = new PhpAmqpLibConnection($this->channel, 'f00b4r');
    }

    public function testSetCallback()
    {
        $call = function ($msg) {
            $this->assertTrue(true);
        };

        $this->channel->expects($this->once())
            ->method('basic_consume')
            ->with(
                'f00b4r',
                '',
                false,
                false,
                false,
                false,
                $call
            );

        $this->conn->setCallback($call);
        $this->conn->listen();
    }

    /**
     * @dataProvider noLocalProvider
     */
    public function testNoLocal($noLocal)
    {
        $conn = new PhpAmqpLibConnection($this->channel, 'f00b4r', $noLocal);

        $call = function ($msg) {
            $this->assertTrue(true);
        };

        $this->channel->expects($this->once())
            ->method('basic_consume')
            ->with(
                'f00b4r',
                '',
                $noLocal,
                false,
                false,
                false,
                $call
            );

        $conn->setCallback($call);
        $conn->listen();
    }

    public function noLocalProvider()
    {
        return [[true], [false]];
    }
}
