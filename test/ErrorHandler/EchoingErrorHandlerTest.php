<?php

namespace Warren\Test\ErrorHandler;

use PHPUnit\Framework\TestCase;

use Warren\ErrorHandler\EchoingErrorHandler;

use Warren\PSR\RabbitMQRequest;

use Warren\Error\UnknownAction;
use Warren\Error\UnknownReplyTo;
use Warren\Error\NoMiddlewares;

class EchoingErrorHandlerTest extends TestCase
{
    public function setUp() : void
    {
        $this->handler = new EchoingErrorHandler;
    }

    /**
     * @dataProvider handleProvider
     */
    public function testHandle($req, $error, $expectedRegex)
    {
        $this->expectOutputRegex($expectedRegex);

        if ($req) {
            $this->handler->setCurrentMessage($req);
        }

        $this->handler->handle($error);
    }

    public function handleProvider()
    {
        return [
            [
                new RabbitMQRequest,
                new \TypeError("Invalid!"),
                "/\[\d+\.\d+, corr_id unknown\]: Invalid!/"
            ], [
                new RabbitMQRequest(['correlation_id' => 'f00b4r']),
                new NoMiddlewares,
                "/\[\d+\.\d+, corr_id f00b4r\]: ".
                    "No middlewares have been provided/"
            ], [
                null,
                new UnknownReplyTo,
                "/\[\d+\.\d+, corr_id unknown\]: ".
                    "Could not determine where to send a reply message/"
            ]
        ];
    }
}
