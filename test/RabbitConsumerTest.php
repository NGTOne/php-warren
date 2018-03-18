<?php

namespace Warren\Test;

use PHPUnit\Framework\TestCase;
use Warren\Test\Stub\StubConnection;
use Warren\Test\Stub\StubAsynchronousAction;
use Warren\Test\Stub\StubSynchronousAction;

use Warren\RabbitConsumer;
use Warren\Error\UnknownAction;
use Warren\Error\ActionAlreadyExists;

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
            ], [
                [
                    function ($req, $res) {
                        return $res->withBody(
                            \GuzzleHttp\Psr7\stream_for('f00b4r')
                        );
                    },
                    function ($req, $res) {
                        return $res->withHeader('foo', 'bar');
                    },
                ],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]',
                ['foo' => ['bar']],
                'f00b4r'
            ]
        ];
    }

    public function testUnknownAction()
    {
        $conn = new StubConnection([], ['action' => 'my_nonexistent_action']);

        $rabbit = new RabbitConsumer($conn);

        $this->expectException(UnknownAction::class);
        $this->expectExceptionMessage(
            'Unknown action "my_nonexistent_action"'
        );

        $rabbit->listen();
    }

    public function testaddAsyncWithExistingAsync()
    {
        $conn = new StubConnection([], []);

        $rabbit = new RabbitConsumer($conn);

        $rabbit->addAsynchronousAction(new StubAsynchronousAction, 'foo');

        $this->expectException(ActionAlreadyExists::class);
        $this->expectExceptionMessage(
            'Action "foo" has already been registered'
        );

        $rabbit->addAsynchronousAction(new StubAsynchronousAction, 'foo');
    }

    public function testaddAsyncWithExistingSync()
    {
        $conn = new StubConnection([], []);

        $rabbit = new RabbitConsumer($conn);

        $rabbit->addSynchronousAction(
            new StubSynchronousAction([], []),
            'foo'
        );

        $this->expectException(ActionAlreadyExists::class);
        $this->expectExceptionMessage(
            'Action "foo" has already been registered'
        );

        $rabbit->addAsynchronousAction(new StubAsynchronousAction, 'foo');
    }

    public function testaddSyncWithExistingAsync()
    {
        $conn = new StubConnection([], []);

        $rabbit = new RabbitConsumer($conn);

        $rabbit->addAsynchronousAction(new StubAsynchronousAction, 'foo');

        $this->expectException(ActionAlreadyExists::class);
        $this->expectExceptionMessage(
            'Action "foo" has already been registered'
        );

        $rabbit->addSynchronousAction(
            new StubSynchronousAction([], []),
            'foo'
        );
    }

    public function testaddSyncWithExistingSync()
    {
        $conn = new StubConnection([], []);

        $rabbit = new RabbitConsumer($conn);

        $rabbit->addSynchronousAction(
            new StubSynchronousAction([], []),
            'foo'
        );

        $this->expectException(ActionAlreadyExists::class);
        $this->expectExceptionMessage(
            'Action "foo" has already been registered'
        );

        $rabbit->addSynchronousAction(
            new StubSynchronousAction([], []),
            'foo'
        );
    }
}
