<?php

namespace Warren;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Warren\ConnectionInterface;

class RabbitConsumer
{
    private $conn;

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
    }
}
