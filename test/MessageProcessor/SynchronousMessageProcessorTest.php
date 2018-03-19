<?php

namespace Warren\Test\MessageProcessor;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use Warren\Test\Stub\StubSynchronousAction;

use Warren\PSR\RabbitMQRequest;
use Warren\PSR\RabbitMQResponse;
use Warren\MiddlewareSet;
use Warren\MessageProcessor\SynchronousMessageProcessor;
use Warren\Error\NoMiddlewares;

class SynchronousMessageProcessorTest extends TestCase
{
    public function testMultipleCallsWithSameStack()
    {
        $wares = new MiddlewareSet();

        $processor = new SynchronousMessageProcessor(
            $wares,
            new StubSynchronousAction('', [])
        );

        $processor->processMessage(new RabbitMQRequest);
        $processor->processMessage(new RabbitMQRequest);

        $this->expectException(NoMiddlewares::class);

        $wares->getMiddlewareStack();
    }

    /**
     * @dataProvider processMessageProvider
     */
    public function testProcessMessage(
        $action,
        $wares,
        $expectedResBody,
        $expectedResHeaders,
        $expectedBody,
        $expectedHeaders
    ) {
        $processor = new SynchronousMessageProcessor(
            $wares,
            $action
        );

        $result = $processor->processMessage(new RabbitMQRequest);

        $this->assertEquals($expectedResBody, (string)$result->getBody());
        $this->assertEquals($expectedResHeaders, $result->getHeaders());

        $this->assertEquals($expectedBody, $action->resBody);
        $this->assertEquals($expectedHeaders, $action->resHeaders);
    }

    public function processMessageProvider()
    {
        return [
            [
                new StubSynchronousAction(null, []),
                new MiddlewareSet(),
                null,
                [],
                null,
                []
            ], [
                new StubSynchronousAction(null, []),
                (new MiddlewareSet)
                    ->addMiddleware(function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res->withHeader('baz', 'qux')
                        );
                    }),
                null,
                ['baz' => ['qux']],
                null,
                ['baz' => ['qux']]
            ], [
                new StubSynchronousAction(null, []),
                (new MiddlewareSet)
                    ->addMiddleware(function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res->withBody(Psr7\stream_for('f00b4r'))
                        );
                    }),
                '',
                [],
                'f00b4r',
                []
            ], [
                new StubSynchronousAction(null, []),
                (new MiddlewareSet)
                    ->addMiddleware(function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res->withBody(Psr7\stream_for('f00b4r'))
                        );
                    })->addMiddleware(function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res->withHeader('baz', 'qux')
                        );
                    }),
                '',
                ['baz' => ['qux']],
                'f00b4r',
                ['baz' => ['qux']]
            ], [
                new StubSynchronousAction('b4rb4z', ['foo' => 'bar']),
                (new MiddlewareSet)
                    ->addMiddleware(function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res
                        )->withBody(Psr7\stream_for('f00b4r'));
                    })->addMiddleware(function ($req, $res, $next) {
                        return $next(
                            $req,
                            $res
                        )->withHeader('baz', 'qux');
                    }),
                'f00b4r',
                ['baz' => ['qux'], 'foo' => ['bar']],
                '',
                []
            ]
        ];
    }
}
