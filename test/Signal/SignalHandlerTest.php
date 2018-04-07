<?php

namespace Warren\Test\Signal;

use Warren\Test\Stub\StubSignalHandler;
use Warren\Signal\Error\InvalidSignal;

use PHPUnit\Framework\TestCase;

class SignalHandlerTest extends TestCase
{
    /**
     * @dataProvider handleReceivedSignalsProvider
     */
    public function testHandleReceivedSignals(
        $signalsToHandle,
        $signalsToSend,
        $expectedSignals
    ) {
        $handler = new StubSignalHandler($signalsToHandle);
        $handler->enable();

        foreach ($signalsToSend as $signal)
        {
            posix_kill(posix_getpid(), $signal);
        }
        pcntl_signal_dispatch();

        $handler->handleReceivedSignals();

        $this->assertEquals($expectedSignals, $handler->signals);
    }

    public function handleReceivedSignalsProvider()
    {
        return [
            [
                ['SIGHUP'],
                [SIGHUP],
                [1 => 'SIGHUP']
            ], [
                ['SIGHUP'],
                [],
                []
            ], [
                ['SIGHUP', 'SIGTERM'],
                [SIGTERM, SIGHUP],
                [1 => 'SIGHUP', 15 => 'SIGTERM']
            ], [
                ['SIGHUP', 'SIGTERM'],
                [SIGTERM],
                [15 => 'SIGTERM']
            ], [
                [SIGHUP, 'SIGTERM'],
                [SIGHUP],
                [1 => 'SIGHUP']
            ]
        ];
    }

    public function testMultipleInvocations()
    {
        $handler = new StubSignalHandler(['SIGHUP', 'SIGTERM']);
        $handler->enable();

        posix_kill(posix_getpid(), SIGHUP);
        pcntl_signal_dispatch();

        $handler->handleReceivedSignals();
        $this->assertEquals([1 => 'SIGHUP'], $handler->signals);

        posix_kill(posix_getpid(), SIGTERM);
        pcntl_signal_dispatch();

        $handler->handleReceivedSignals();
        $this->assertEquals([15 => 'SIGTERM'], $handler->signals);
    }

    public function testRestoringOriginalHandlers()
    {
        pcntl_signal(SIGHUP, SIG_IGN);

        $handler = new StubSignalHandler(['SIGHUP']);
        $handler->enable();

        posix_kill(posix_getpid(), SIGHUP);
        pcntl_signal_dispatch();

        $handler->handleReceivedSignals();
        $this->assertEquals([1 => 'SIGHUP'], $handler->signals);

        $handler->disable();
        $this->assertEquals(SIG_IGN, pcntl_signal_get_handler(SIGHUP));
    }

    // Calls to enable() should be idempotent
    public function testMultipleEnables()
    {
        pcntl_signal(SIGHUP, SIG_IGN);
        $handler = new StubSignalHandler(['SIGHUP']);
        $handler->enable();
        $handler->enable();

        $handler->disable();
        $this->assertEquals(SIG_IGN, pcntl_signal_get_handler(SIGHUP));
    }

    /**
     * @dataProvider invalidSignalProvider
     */
    public function testInvalidSignal($signals, $expectedMsg)
    {
        $this->expectException(InvalidSignal::class);
        $this->expectExceptionMessage($expectedMsg);

        new StubSignalHandler($signals);
    }

    public function invalidSignalProvider()
    {
        return [
            [
                [[]], "array is not a valid signal."
            ], [
                [new \stdClass], "object is not a valid signal."
            ], [
                [true], "boolean is not a valid signal."
            ], [
                [2.1], "double is not a valid signal."
            ], [
                [null], "NULL is not a valid signal."
            ]
        ];
    }
}
