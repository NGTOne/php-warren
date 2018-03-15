<?php

namespace Warren;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Warren\ConnectionInterface;
use Warren\AsynchronousAction;
use Warren\SynchronousAction;
use Warren\MiddlewareSet;

class RabbitConsumer
{
    private $conn;
    private $asyncActions = [];
    private $syncActions = [];

    private $asynchronousMiddlewares;
    private $synchronousMiddlewares;

    private $replyTo = 'reply_to';
    private $actionHeader = 'action';

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
        $this->conn->connect();

        $this->asynchronousMiddlewares = new MiddlewareSet;
        $this->synchronousMiddlewares = new MiddlewareSet;
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

    public function addSynchronousMiddleware(
        callable $ware
    ) : RabbitConsumer {
        $this->synchronousMiddlewares->addMiddleware($ware);
        return $this;
    }

    public function addAsynchronousMiddleware(
        callable $ware
    ) : RabbitConsumer {
        $this->asynchronousMiddlewares->addMiddleware($ware);
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
