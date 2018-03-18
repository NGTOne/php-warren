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
        $expectedHeaders,
        $expectedBody
    ) {
        $conn = $this->getMockBuilder(StubConnection::class)
            ->setMethods(['sendMessage'])
            ->setConstructorArgs([$body, $headers])
            ->getMock();

        $conn->expects($this->never())->method('sendMessage');

        $rabbit = new RabbitConsumer($conn);

        $action = new StubAsynchronousAction;

        $rabbit->addAsynchronousAction($action, 'my_cool_action');

        foreach ($middlewares as $ware) {
            $rabbit->addAsynchronousMiddleware($ware);
        }

        $rabbit->listen();

        $this->assertEquals($expectedHeaders, $action->reqHeaders);
        $this->assertEquals($expectedBody, $action->reqBody);
    }

    public function asyncMessageSuccessProvider()
    {
        return [
            [
                [],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]'
            ]
        ];
    }
}
