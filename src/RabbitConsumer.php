<?php

namespace Warren;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Warren\ConnectionInterface;
use Warren\AsynchronousAction;
use Warren\SynchronousAction;
use Warren\MiddlewareSet;
use Warren\ErrorHandler;
use Warren\SignalHandler;
use Warren\ErrorHandler\EchoingErrorHandler;
use Warren\MessageProcessor\AsynchronousMessageProcessor;
use Warren\MessageProcessor\SynchronousMessageProcessor;
use Warren\Error\UnknownAction;
use Warren\Error\ActionAlreadyExists;
use Warren\Signal\ExitingSignalHandler;

class RabbitConsumer
{
    private $conn;
    private $asyncActions = [];
    private $syncActions = [];

    private $asyncMiddlewares;
    private $syncMiddlewares;
    private $errorHandler;
    private $replyErrorHandler;
    private $signalHandler;

    private $actionHeader = 'action';

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;

        $this->asyncMiddlewares = new MiddlewareSet;
        $this->syncMiddlewares = new MiddlewareSet;

        $this->errorHandler = new EchoingErrorHandler;
        $this->replyErrorHandler = new EchoingErrorHandler;
        $this->signalHandler = new ExitingSignalHandler([
            'SIGTERM',
            'SIGHUP',
            'SIGINT'
        ]);
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
        if ($this->actionExists($name)) {
            throw new ActionAlreadyExists($name);
        }

        $this->asyncActions[$name] = $action;
        return $this;
    }

    public function addSynchronousAction(
        SynchronousAction $action,
        string $name
    ) : RabbitConsumer {
        if ($this->actionExists($name)) {
            throw new ActionAlreadyExists($name);
        }

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

    public function setSignalHandler(
        SignalHandler $handler
    ) : RabbitConsumer {
        $this->signalHandler = $handler;
        return $this;
    }

    public function setErrorHandler(
        ErrorHandler $handler
    ) : RabbitConsumer {
        $this->errorHandler = $handler;
        return $this;
    }

    public function setReplyErrorHandler(
        ErrorHandler $handler
    ) : RabbitConsumer {
        $this->replyErrorHandler = $handler;
        return $this;
    }

    private function actionExists(string $action)
    {
        return array_search(
            $action,
            array_keys($this->asyncActions)
        ) !== false or array_search(
            $action,
            array_keys($this->syncActions)
        ) !== false;
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
            array_keys($this->syncActions)
        ) !== false ? new SynchronousMessageProcessor(
            $this->syncMiddlewares,
            $this->syncActions[$action]
        ) : null;
    }

    private function processMsg($msg)
    {
        $this->signalHandler->enable();

        try {
            $req = $this->conn->convertMessage($msg);

            $this->errorHandler->setCurrentMessage($req);
            $this->replyErrorHandler->setCurrentMessage($req);

            $action = $req->getHeaderLine($this->actionHeader);

            $proc = $this->getAsyncProcessor($action) ??
                $this->getSyncProcessor($action);

            if (!$proc) {
                throw new UnknownAction($action);
            }

            $result = $proc->processMessage($req);
        } catch (\Throwable $e) {
            $result = $this->errorHandler->handle($e);
        }

        try {
            if ($proc instanceof SynchronousMessageProcessor) {
                $this->conn->sendResponse(
                    $msg,
                    $result
                );
            }
        } catch (\Throwable $e) {
            $this->replyErrorHandler->handle($e);
        }

        try {
             // Something goes wrong acking the message, we're 6 different
             // kinds of screwed anyways
             $this->conn->acknowledgeMessage($msg);
        } catch (\Throwable $e) {
            $this->replyErrorHandler->handle($e);
        }

        $this->signalHandler->handleReceivedSignals();
        $this->signalHandler->disable();
    }

    public function listen() : void
    {
        $this->conn->setCallback(function ($msg) {
            $this->processMsg($msg);
        });

        $this->conn->listen();
    }
}
