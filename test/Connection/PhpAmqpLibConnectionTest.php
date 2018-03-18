<?php

namespace Warren\Test\Connection;

use PHPUnit\Framework\TestCase;
use Warren\Connection\PhpAmqpLibConnection;
use Warren\Test\Stub\StubAMQPChannel;

use Warren\PSR\RabbitMQRequest;
use Warren\PSR\RabbitMQResponse;

use PhpAmqpLib\Message\AMQPMessage;

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

    public function testAcknowledgeMessage()
    {
        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('f00b4r');

        $msg = new AMQPMessage();
        $msg->delivery_info['delivery_tag'] = 'f00b4r';

        $this->conn->acknowledgeMessage($msg);
    }

    /**
     * @dataProvider convertMessageProvider
     */
    public function testConvertMessage($msg, $expected)
    {
        $result = $this->conn->convertMessage($msg);

        $this->assertEquals(
            (string)$expected->getBody(),
            (string)$result->getBody()
        );
        $this->assertEquals(
            $expected->getHeaders(),
            $result->getHeaders()
        );
    }

    public function convertMessageProvider()
    {
        return [
            [
                new AMQPMessage(),
                new RabbitMQRequest([], '')
            ], [
                new AMQPMessage('f00b4r'),
                new RabbitMQRequest([], 'f00b4r')
            ], [
                new AMQPMessage('', ['reply_to' => 'bar']),
                new RabbitMQRequest(['reply_to' => 'bar'], '')
            ], [
                new AMQPMessage('f00b4r', ['reply_to' => 'bar']),
                new RabbitMQRequest(['reply_to' => 'bar'], 'f00b4r')
            ]
        ];
    }

    /**
     * @dataProvider sendMessageProvider
     */
    public function testSendMessage(
        $response,
        $expectedBody,
        $expectedProperties
    ) {
        $this->channel->expects($this->once())
            ->method('basic_publish')
            ->with($this->callback(function ($msg) use (
                $expectedBody,
                $expectedProperties
            ) {
                $this->assertEquals(
                    $expectedBody,
                    $msg->getBody()
                );
                $this->assertEquals(
                    $expectedProperties,
                    $msg->get_properties()
                );

                return true;
            }), '', 'the_reply_queue');

        $this->conn->sendMessage(
            $response,
            new AMQPMessage,
            'the_reply_queue'
        );
    }

    public function sendMessageProvider()
    {
        return [
            [
                new RabbitMQResponse(['foo' => 'bar'], 'f00b4r'),
                'f00b4r',
                []
            ], [
                new RabbitMQResponse(['type' => 'a_serious_message']),
                '',
                ['type' => 'a_serious_message']
            ], [
                new RabbitMQResponse(),
                '',
                []
            ], [
                new RabbitMQResponse(['type' => 'msg'], 'f00b4r'),
                'f00b4r',
                ['type' => 'msg']
            ], [
                new RabbitMQResponse([
                    'type' => ['msg', 'something']
                ], 'f00b4r'),
                'f00b4r',
                ['type' => 'msg, something']
            ]
        ];
    }
}
