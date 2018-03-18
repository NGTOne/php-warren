<?php

namespace Warren\Test;

use PHPUnit\Framework\TestCase;
use Warren\Test\Stub\StubConnection;
use Warren\Test\Stub\StubAsynchronousAction;
use Warren\Test\Stub\StubSynchronousAction;

use Warren\RabbitConsumer;

class RabbitConsumerTest extends TestCase
{
    /**
     * @dataProvider asyncMessageSuccessProvider
     */
    public function testAsyncMessageSuccess(
        $middlewares,
        $headers,
        $body,
        $expectedReqHeaders,
        $expectedReqBody,
        $expectedResHeaders,
        $expectedResBody
    ) {
        $conn = $this->getMockBuilder(StubConnection::class)
            ->setMethods(['sendMessage', 'acknowledgeMessage'])
            ->setConstructorArgs([$body, $headers])
            ->getMock();

        $conn->expects($this->never())
            ->method('sendMessage');
        $conn->expects($this->once())
            ->method('acknowledgeMessage')
            ->with($this->identicalTo(json_encode([
                'body' => $body,
                'header' => $headers
            ])));

        $rabbit = new RabbitConsumer($conn);

        $action = new StubAsynchronousAction;

        $rabbit->addAsynchronousAction($action, 'my_cool_action');

        foreach ($middlewares as $ware) {
            $rabbit->addAsynchronousMiddleware($ware);
        }

        $rabbit->listen();

        $this->assertEquals($expectedReqHeaders, $action->reqHeaders);
        $this->assertEquals($expectedReqBody, $action->reqBody);
        $this->assertEquals($expectedResHeaders, $action->resHeaders);
        $this->assertEquals($expectedResBody, $action->resBody);
    }

    public function asyncMessageSuccessProvider()
    {
        return [
            [
                [],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]',
                [],
                ''
            ], [
                [function ($req, $res) {
                    return $res->withHeader('foo', 'bar');
                }],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]',
                ['foo' => ['bar']],
                ''
            ]
        ];
    }
}
