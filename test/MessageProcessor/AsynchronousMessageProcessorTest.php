<?php

namespace Warren\Test\MessageProcessor;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use Warren\Test\Stub\StubAsynchronousAction;

use Warren\PSR\RabbitMQRequest;
use Warren\PSR\RabbitMQResponse;
use Warren\MiddlewareSet;
use Warren\MessageProcessor\AsynchronousMessageProcessor;

class AsynchronousMessageProcessTest extends TestCase
{
    private $action;

    public function setUp() : void
    {
        $this->action = new StubAsynchronousAction();
    }

    /**
     * @dataProvider processMessageProvider
     */
    public function testProcessMessage(
        $wares,
        $expectedBody,
        $expectedHeaders
    ) {
        $processor = new AsynchronousMessageProcessor(
            $wares,
            $this->action
        );

        $result = $processor->processMessage(new RabbitMQRequest);

        $this->assertEquals(new RabbitMQResponse, $result);

        $this->assertEquals($expectedBody, $this->action->resBody);
        $this->assertEquals($expectedHeaders, $this->action->resHeaders);
    }

    public function processMessageProvider()
    {
        return [
            [
                new MiddlewareSet(),
                null,
                []
            ], [
                (new MiddlewareSet)->addMiddleware(function ($req, $res) {
                    return $res->withHeader('baz', 'qux');
                }),
                null,
                ['baz' => ['qux']]
            ], [
                (new MiddlewareSet)->addMiddleware(function ($req, $res) {
                    return $res->withBody(Psr7\stream_for('f00b4r'));
                }),
                'f00b4r',
                []
            ], [
                (new MiddlewareSet)->addMiddleware(function ($req, $res) {
                    return $res->withBody(Psr7\stream_for('f00b4r'));
                })->addMiddleware(function ($req, $res) {
                    return $res->withHeader('baz', 'qux');
                }),
                'f00b4r',
                ['baz' => ['qux']]
            ]
        ];
    }
}