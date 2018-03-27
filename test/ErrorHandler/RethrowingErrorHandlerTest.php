<?php

namespace Warren\Test\ErrorHandler;

use PHPUnit\Framework\TestCase;

use Warren\ErrorHandler\RethrowingErrorHandler;

use Warren\Error\UnknownAction;
use Warren\Error\UnknownReplyTo;
use Warren\Error\NoMiddlewares;

class RethrowingErrorHandlerTest extends TestCase
{
    public function setUp() : void
    {
        $this->handler = new RethrowingErrorHandler;
    }

    /**
     * @dataProvider handleProvider
     */
    public function testHandle($error, $expectedType, $expectedMsg)
    {
        $this->expectException($expectedType);
        $this->expectExceptionMessage($expectedMsg);

        $this->handler->handle($error);
    }

    public function handleProvider()
    {
        return [
            [new \TypeError("Invalid!"), \TypeError::class, "Invalid!"],
            [
                new NoMiddlewares,
                NoMiddlewares::class,
                "No middlewares have been provided"
            ], [
                new UnknownAction("f00b4r"),
                UnknownAction::class,
                'Unknown action "f00b4r"'
            ], [
                new UnknownReplyTo,
                UnknownReplyTo::class,
                "Could not determine where to send a reply message"
            ]
        ];
    }
}
