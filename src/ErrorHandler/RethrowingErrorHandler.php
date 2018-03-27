<?php

namespace Warren\ErrorHandler;

use Warren\ErrorHandler;

class RethrowingErrorHandler
{
    public function handle(\Throwable $error)
    {
        throw $error;
    }
}
