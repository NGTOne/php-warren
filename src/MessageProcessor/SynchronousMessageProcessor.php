<?php

namespace Warren\MessageProcessor;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Warren\MiddlewareSet;
use Warren\SynchronousAction;
use Warren\PSR\RabbitMQResponse;

class SynchronousMessageProcessor extends MessageProcessor
{
    private $action;

    public function __construct(
        MiddlewareSet $wares,
        SynchronousAction $action
    ) {
        $this->action = $action;
        parent::__construct($wares);
    }

    protected function finalMiddlewareLayer() : callable
    {
        return function (RequestInterface $req, ResponseInterface $res) {
            return $this->action->performSynchronousAction($req, $res);
        };
    }
}
