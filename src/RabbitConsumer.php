<?php

namespace Warren;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Warren\ConnectionInterface;
use Warren\AsynchronousAction;
use Warren\SynchronousAction;
use Warren\MiddlewareSet;

use Warren\MessageProcessor\AsynchronousMessageProcessor;
use Warren\MessageProcessor\SynchronousMessageProcessor;

class RabbitConsumer
{
    private $conn;
    private $asyncActions = [];
    private $syncActions = [];

    private $asyncMiddlewares;
    private $syncMiddlewares;

    private $replyTo = 'reply_to';
    private $actionHeader = 'action';

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;

        $this->asyncMiddlewares = new MiddlewareSet;
        $this->syncMiddlewares = new MiddlewareSet;
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
        $this->syncMiddlewares->addMiddleware($ware);
        return $this;
    }

    public function addAsynchronousMiddleware(
        callable $ware
    ) : RabbitConsumer {
        $this->asyncMiddlewares->addMiddleware($ware);
        return $this;
    }

    private function getAsyncProcessor(
        string $action
    ) : ?AsynchronousMessageProcessor {
        return array_search(
            $action,
            array_keys($this->asyncActions)
        ) !== false ? new AsynchronousMessageProcessor(
            $this->asyncMiddlewares,
            $this->asyncActions[$action]
        ) : null;
    }

    private function getSyncProcessor(
        string $action
    ) : ?SynchronousMessageProcessor {
        return array_search(
            $action,
            array_keys($this->asyncActions)
        ) !== false ? new SynchronousMessageProcessor(
            $this->asyncMiddlewares,
            $this->asyncActions[$action]
        ) : null;
    }

    private function processMsg($msg)
    {
        $req = $this->conn->convertMessage($msg);

        $action = $req->getHeaderLine($this->actionHeader);

        $proc = $this->getAsyncProcessor($action) ??
            $this->getSyncProcessor($action);

        $result = $proc->processMessage($req);

        $this->conn->acknowledgeMessage($msg);
    }

    public function listen() : void
    {
        $this->conn->setCallback(function ($msg) {
            $this->processMsg($msg);
        });

        $this->conn->listen();
    }
}
