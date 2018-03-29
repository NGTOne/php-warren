<?php

namespace Warren\Test;

use PHPUnit\Framework\TestCase;
use Warren\Test\Stub\StubConnection;
use Warren\Test\Stub\StubAsynchronousAction;
use Warren\Test\Stub\StubSynchronousAction;

use Warren\PSR\RabbitMQResponse;
use Warren\RabbitConsumer;
use Warren\Error\UnknownAction;
use Warren\Error\ActionAlreadyExists;

use Warren\ErrorHandler\EchoingErrorHandler;
use Warren\ErrorHandler\RethrowingErrorHandler;

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
            ->setMethods(['sendResponse', 'acknowledgeMessage'])
            ->setConstructorArgs([$body, $headers])
            ->getMock();

        $conn->expects($this->never())
            ->method('sendResponse');
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
                [function ($req, $res, $next) {
                    return $next($req, $res->withHeader('foo', 'bar'));
                }],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]',
                ['foo' => ['bar']],
                ''
            ], [
                [function ($req, $res, $next) {
                    return $next($req, $res->withBody(
                        \GuzzleHttp\Psr7\stream_for('f00b4r')
                    ));
                }],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]',
                [],
                'f00b4r'
            ], [
                [
                    function ($req, $res, $next) {
                        return $next($req, $res->withBody(
                            \GuzzleHttp\Psr7\stream_for('f00b4r')
                        ));
                    },
                    function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res->withHeader('foo', 'bar')
                        );
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

    /**
     * @dataProvider syncMessageSuccessProvider
     */
    public function testSyncMessageSuccess(
        $middlewares,
        $headers,
        $body,
        $expectedReqHeaders,
        $expectedReqBody,
        $expectedIncomingResHeaders,
        $expectedIncomingResBody
    ) {
        $conn = $this->getMockBuilder(StubConnection::class)
            ->setMethods(['sendResponse', 'acknowledgeMessage'])
            ->setConstructorArgs([$body, $headers])
            ->getMock();

        $expectedResHeaders = array_merge($expectedIncomingResHeaders, [
            'some_header' => ['my_header']
        ]);

        $conn->expects($this->once())
            ->method('sendResponse')
            ->with($this->anything(), $this->callback(
                function ($res) use ($expectedResHeaders) {
                    $this->assertEquals('f00b4r', $res->getBody());
                    $this->assertEquals(
                        $expectedResHeaders,
                        $res->getHeaders()
                    );

                    return true;
                }
            ));
        $conn->expects($this->once())
            ->method('acknowledgeMessage')
            ->with($this->identicalTo(json_encode([
                'body' => $body,
                'header' => $headers
            ])));

        $rabbit = new RabbitConsumer($conn);

        $action = new StubSynchronousAction('f00b4r', [
            'some_header' => 'my_header'
        ]);

        $rabbit->addSynchronousAction($action, 'my_cool_action');

        foreach ($middlewares as $ware) {
            $rabbit->addSynchronousMiddleware($ware);
        }

        $rabbit->listen();

        $this->assertEquals($expectedReqHeaders, $action->reqHeaders);
        $this->assertEquals($expectedReqBody, $action->reqBody);
        $this->assertEquals(
            $expectedIncomingResHeaders,
            $action->resHeaders
        );
        $this->assertEquals($expectedIncomingResBody, $action->resBody);
    }

    public function syncMessageSuccessProvider()
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
                [function ($req, $res, $next) {
                    return $next($req, $res->withHeader('foo', 'bar'));
                }],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]',
                ['foo' => ['bar']],
                ''
            ], [
                [function ($req, $res, $next) {
                    return $next($req, $res->withBody(
                        \GuzzleHttp\Psr7\stream_for('f00b4r')
                    ));
                }],
                ['action' => 'my_cool_action'],
                [],
                ['action' => ['my_cool_action']],
                '[]',
                [],
                'f00b4r'
            ], [
                [
                    function ($req, $res, $next) {
                        return $next($req, $res->withBody(
                            \GuzzleHttp\Psr7\stream_for('f00b4r')
                        ));
                    },
                    function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res->withHeader('foo', 'bar')
                        );
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

        $rabbit = (new RabbitConsumer($conn))
            ->setErrorHandler(new RethrowingErrorHandler);

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

    public function testReplyError()
    {
        $conn = $this->getMockBuilder(StubConnection::class)
            ->setConstructorArgs([[], ['action' => 'my_action']])
            ->setMethods(['sendResponse'])
            ->getMock();

        $handler = new RethrowingErrorHandler();

        $conn->expects($this->once())
            ->method('sendResponse')
            ->will($this->throwException(
                new \Exception('Something went wrong!')
            ));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Something went wrong!");

        $rabbit = new RabbitConsumer($conn);

        $rabbit->setReplyErrorHandler($handler);

        $rabbit->addSynchronousAction(
            new StubSynchronousAction('', []),
            'my_action'
        );

        $rabbit->listen();
    }

    /**
     * @dataProvider fluentInterfaceProvider
     */
    public function testFluentInterface($call, $args)
    {
        $conn = new StubConnection([], []);
        $rabbit = new RabbitConsumer($conn);

        $result = $rabbit->$call(...$args);

        $this->assertSame($rabbit, $result);
    }

    public function fluentInterfaceProvider()
    {
        return [
            [
                'setActionHeader',
                ['f00b4r']
            ], [
                'addAsynchronousAction',
                [new StubAsynchronousAction, 'foo']
            ], [
                'addSynchronousAction',
                [new StubSynchronousAction([], []), 'foo']
            ], [
                'addAsynchronousMiddleware',
                [function ($req, $res) {}]
            ], [
                'addSynchronousMiddleware',
                [function ($req, $res) {}]
            ], [
                'setErrorHandler',
                [new EchoingErrorHandler]
            ], [
                'setReplyErrorHandler',
                [new EchoingErrorHandler]
            ]
        ];
    }
}
