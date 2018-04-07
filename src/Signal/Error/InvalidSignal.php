<?php

namespace Warren\Signal\Error;

class InvalidSignal extends \Exception
{
    public function __construct($signal, $previous = null)
    {
        parent::__construct(
            gettype($signal)." is not a valid signal.",
            null,
            $previous
        );
    }
}
