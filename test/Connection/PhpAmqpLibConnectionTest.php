<?php

namespace Warren\Test\Connection;

use PHPUnit\Framework\TestCase;
use Warren\Connection\PhpAmqpLibConnection;
use Warren\Test\Stub\StubAMQPChannel;

use Warren\Error\UnknownReplyTo;
use Warren\PSR\RabbitMQRequest;
use Warren\PSR\RabbitMQResponse;

use PhpAmqpLib\Wire\AMQPTable;
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
                new AMQPMessage('', [
                    'application_headers' => new AMQPTable
                ]),
                new RabbitMQRequest([], '')
            ], [
                new AMQPMessage('f00b4r', [
                    'application_headers' => new AMQPTable
                ]),
                new RabbitMQRequest([], 'f00b4r')
            ], [
                new AMQPMessage('', [
                    'application_headers' => new AMQPTable([
                        'foo' => 'bar'
                    ])
                ]),
                new RabbitMQRequest(['foo' => 'bar'], '')
            ], [
                new AMQPMessage('f00b4r', [
                    'application_headers' => new AMQPTable([
                        'foo' => 'bar'
                    ])
                ]),
                new RabbitMQRequest(['foo' => 'bar'], 'f00b4r')
            ], [
                // Even if the headers are missing, we shouldn't crash
                new AMQPMessage('f00b4r'),
                new RabbitMQRequest([], 'f00b4r')
            ]
        ];
    }

    public function testSendResponseMissingReplyTo()
    {
        $this->expectException(UnknownReplyTo::class);

        $this->conn->sendResponse(new AMQPMessage, new RabbitMQResponse);
    }

    /**
     * @dataProvider sendResponseProvider
     */
    public function testSendResponse(
        $response,
        $message,
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

        $this->conn->sendResponse($message, $response);
    }

    public function sendResponseProvider()
    {
        return [
            [
                new RabbitMQResponse(['foo' => 'bar'], 'f00b4r'),
                new AMQPMessage(null, ['reply_to' => 'the_reply_queue']),
                'f00b4r',
                [
                    'application_headers' => new AMQPTable([
                        'foo' => ['bar']
                    ]),
                    'reply_to' => 'the_reply_queue'
                ]
            ], [
                new RabbitMQResponse(),
                new AMQPMessage(null, ['reply_to' => 'the_reply_queue']),
                '',
                [
                    'application_headers' => new AMQPTable,
                    'reply_to' => 'the_reply_queue'
                ]
            ], [
                new RabbitMQResponse(['bar' => 'baz'], 'f00b4r'),
                new AMQPMessage(null, ['reply_to' => 'the_reply_queue']),
                'f00b4r',
                [
                    'application_headers' => new AMQPTable([
                        'bar' => ['baz']
                    ]),
                    'reply_to' => 'the_reply_queue'
                ]
            ], [
                new RabbitMQResponse([
                    'stuff' => ['msg', 'something']
                ], 'f00b4r'),
                new AMQPMessage(null, ['reply_to' => 'the_reply_queue']),
                'f00b4r',
                [
                    'application_headers' => new AMQPTable([
                        'stuff' => ['msg', 'something']
                    ]),
                    'reply_to' => 'the_reply_queue'
                ]
            ], [
                new RabbitMQResponse,
                new AMQPMessage(null, [
                    'reply_to' => 'the_reply_queue',
                    'correlation_id' => 'my_corr_id'
                ]),
                '',
                [
                    'application_headers' => new AMQPTable,
                    'reply_to' => 'the_reply_queue',
                    'correlation_id' => 'my_corr_id'
                ]
            ]
        ];
    }

    /**
     * @dataProvider setHeaderPropertiesProvider
     */
    public function testSetHeaderProperties($mappings, $msg, $expected)
    {
        $result = $this->conn->setHeaderProperties($mappings);
        $this->assertSame($this->conn, $result);

        $result = $this->conn->convertMessage($msg);

        $this->assertEquals($expected, $result);
    }

    public function setHeaderPropertiesProvider()
    {
        return [
            [
                [],
                new AMQPMessage('', [
                    'correlation_id' => 'f00b4r'
                ]),
                new RabbitMQRequest
            ], [
                [
                    'correlation_id' => 'corr_id'
                ],
                new AMQPMessage('', [
                    'correlation_id' => 'f00b4r',
                    'type' => 'magic'
                ]),
                new RabbitMQRequest(['corr_id' => 'f00b4r'])
            ], [
                [
                    'correlation_id' => 'abc',
                    'type' => 'my_stuff'
                ],
                new AMQPMessage('', [
                    'correlation_id' => 'f00b4r',
                    'type' => 'magic'
                ]),
                new RabbitMQRequest([
                    'abc' => 'f00b4r',
                    'my_stuff' => 'magic'
                ])
            ], [
                [
                    'application_headers' => 'abc'
                ],
                new AMQPMessage('', [
                    'application_headers' => new AMQPTable([
                        'foo' => 'bar'
                    ]),
                ]),
                new RabbitMQRequest(['foo' => 'bar'])
            ]
        ];
    }

    /**
     * @dataProvider setHeaderPropertiesErrorProvider
     */
    public function testSetHeaderPropertiesErrors($mappings, $expectedMsg)
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage($expectedMsg);

        $this->conn->setHeaderProperties($mappings);
    }

    public function setHeaderPropertiesErrorProvider()
    {
        return [
            [
                [5 => 'foo'],
                "All RabbitMQ properties are named using strings"
            ], [
                [null => 'foo'],
                "All RabbitMQ properties are named using strings"
            ], [
                ['correlation_id' => null],
                "Attempting to map correlation_id to a non-string header"
            ], [
                ['correlation_id' => 5],
                "Attempting to map correlation_id to a non-string header"
            ], [
                ['correlation_id' => ['foo']],
                "Attempting to map correlation_id to a non-string header"
            ], [
                ['correlation_id' => null],
                "Attempting to map correlation_id to a non-string header"
            ]
        ];
    }
}
