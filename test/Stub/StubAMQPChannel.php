<?php

namespace Warren\Test\Stub;

use PhpAmqpLib\Channel\AMQPChannel;

// In order to prevent the tests from busy-waiting
class StubAMQPChannel extends AMQPChannel
{
    public function wait(
        $allowed_methods = NULL,
        $non_blocking = false,
        $timeout = 0
    ) {
        $this->callbacks = [];
    }
}
