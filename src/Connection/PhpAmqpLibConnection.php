<?php

namespace Warren\Connection;

use Warren\ConnectionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpAmqpLib\Channel\AMQPChannel;

use Warren\PSR\RabbitMQRequest;

class PhpAmqpLibConnection implements ConnectionInterface
{
    private $channel;
    private $callback;

    private $queue;
    private $noLocal;

    public function __construct(
        AMQPChannel $channel,
        string $queue,
        bool $noLocal = false
    ) {
        $this->channel = $channel;

        $this->queue = $queue;
        $this->noLocal = $noLocal;
    }

    public function setCallback(callable $callback) : void
    {
        $this->callback = $callback;
    }

    public function acknowledgeMessage($msg) : void
    {
        $this->channel->basic_ack(
            $message->delivery_info['delivery_tag']
        );
    }

    public function convertMessage($msg) : RequestInterface
    {
        return new RabbitMQRequest(
            $msg->get_properties(),
            $msg->getBody()
        );
    }

    public function listen() : void
    {
        $this->channel->basic_consume(
            $this->queue,
            '',
            $this->noLocal,
            false,
            false,
            false,
            $this->callback
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    private function convertToAMQPMessage(
        ResponseInterface $response
    ) : AMQPMessage {
        return new AMQPMessage((string)$response->getBody(), array_map(
            function ($header) {
                return implode($header, ", ");
            },
            $response->getHeaders()
        );
    }

    public function sendMessage(
        ResponseInterface $response,
        $originalMsg,
        string $replyTo
    ) : void {
        $newMsg = $this->convertToAMQPMessage($response);

        $this->channel->basic_publish(
            $newMsg,
            '',
            $replyTo
        );
    }
}
