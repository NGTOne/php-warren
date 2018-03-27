<?php

namespace Warren\ErrorHandler;

use Warren\ErrorHandler;

class RethrowingErrorHandler implements ErrorHandler
{
    public function handle(\Throwable $error)
    {
        throw $error;
    }
}
