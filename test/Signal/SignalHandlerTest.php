<?php

namespace Warren\Test\Signal;

use Warren\Test\Stub\StubSignalHandler;

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
}
