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
            ]
        ];
    }

    public function testMultipleInvocations()
    {
        $handler = new StubSignalHandler(['SIGHUP', 'SIGTERM']);

        posix_kill(posix_getpid(), SIGHUP);
        pcntl_signal_dispatch();

        $handler->handleReceivedSignals();
        $this->assertEquals([1 => 'SIGHUP'], $handler->signals);

        posix_kill(posix_getpid(), SIGTERM);
        pcntl_signal_dispatch();

        $handler->handleReceivedSignals();
        $this->assertEquals([15 => 'SIGTERM'], $handler->signals);
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
