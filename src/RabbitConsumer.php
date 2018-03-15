<?php

namespace Warren;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Warren\ConnectionInterface;
use Warren\AsynchronousAction;
use Warren\SynchronousAction;

class RabbitConsumer
{
    private $conn;
    private $asyncActions = [];
    private $syncActions = [];

    private $replyTo = 'reply_to';
    private $actionHeader = 'action';

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
        $this->conn->connect();
    }

    public function setReplyToHeader(string $replyTo) : RabbitConsumer
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function setActionHeader(string $action) : RabbitConsumer
    {
        $this->action = $action;
        return $this;
    }

    public function addAsynchronousAction(
        AsynchronousAction $action,
        string $name
    ) : RabbitConsumer {
        $this->asyncActions[$name] = $action;
        return $this;
    }

    public function addSynchronousAction(
        SynchronousAction $action,
        string $name
    ) : RabbitConsumer {
        $this->syncActions[$name] = $action;
        return $this;
    }

    private function processMsg($msg)
    {
        $req = $this->conn->convertMessage($msg);
    }

    public function listen() : void
    {
        $this->conn->setCallback(function($msg) {
            $this->processMsg($msg);
        });
    }
}
