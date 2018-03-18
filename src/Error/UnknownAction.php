<?php

namespace Warren\Error;

class UnknownAction extends \Exception
{
    public function __construct(string $action)
    {
        parent::__construct("Unknown action \"$action\"");
    }
}
