<?php

namespace Warren;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Warren\ConnectionInterface;
use Warren\Action;

class RabbitConsumer
{
    private $conn;
    private $asyncActions = [];
    private $syncActions = [];

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
        $this->conn->connect();
    }

    public function addAsynchronousAction(
        Action $action,
        string $name
    ) : RabbitConsumer {
        $this->asyncActions[$name] = $action;
        return $this;
    }

    public function addSynchronousAction(
        Action $action,
        string $name
    ) : RabbitConsumer {
        $this->syncActions[$name] = $action;
        return $this;
    }
}
