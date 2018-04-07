<?php

namespace Warren\Test\Stub;

use Warren\Signal\SignalHandler;

class StubSignalHandler extends SignalHandler
{
    public $signals = [];

    protected function handleSignals() : void
    {
        $this->signals = $this->receivedSignals();
    }
}
