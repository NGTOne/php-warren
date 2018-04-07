<?php

namespace Warren\Signal;

use Warren\Signal\SignalHandler;

class ExitingSignalHandler extends SignalHandler
{
    /**
     * There's _really_ no way to test this
     * @codeCoverageIgnore
     */
    protected function handleSignals() : void
    {
        exit;
    }
}
