<?php

namespace Warren\Error;

class ActionAlreadyExists extends \Exception
{
    public function __construct(string $action)
    {
        parent::__construct(
            "Action \"$action\" has already been registered"
        );
    }
}
