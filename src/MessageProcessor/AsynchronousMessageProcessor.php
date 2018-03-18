<?php

namespace Warren\MessageProcessor;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Warren\MiddlewareSet;
use Warren\AsynchronousAction;
use Warren\PSR\RabbitMQResponse;

class AsynchronousMessageProcessor extends MessageProcessor
{
    private $action;

    public function __construct(
        MiddlewareSet $wares,
        AsynchronousAction $action
    ) {
        $this->action = $action;
        parent::__construct($wares);
    }

    protected function finalMiddlewareLayer() : callable
    {
        return function (RequestInterface $req, ResponseInterface $res) {
            $this->action->performAsynchronousAction($req, $res);

            return new RabbitMQResponse();
        };
    }
}
