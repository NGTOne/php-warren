<?php

namespace Warren\Error;

class UnknownReplyTo extends \Exception
{
    public function __construct($previous = null)
    {
        parent::__construct(
            "Could not find where to send a reply message to",
            null,
            $previous
        );
    }
}
