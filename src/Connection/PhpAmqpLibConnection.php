<?php

namespace Warren\Connection;

use Warren\ConnectionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

use Warren\Error\UnknownReplyTo;
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
            $msg->delivery_info['delivery_tag']
        );
    }

    public function convertMessage($msg) : RequestInterface
    {
        try {
            $headers = $msg->get('application_headers')->getNativeData();
        } catch (\OutOfBoundsException $e) {
            $headers = [];
        }

        return new RabbitMQRequest(
            $headers,
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

        // @codeCoverageIgnoreStart
        // I've really got _no_ idea how to unit test this
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        // @codeCoverageIgnoreEnd
    }

    private function convertToAMQPMessage(
        ResponseInterface $response
    ) : AMQPMessage {
        return new AMQPMessage((string)$response->getBody(), [
            'application_headers' => new AMQPTable(
                $response->getHeaders()
            )
        ]);
    }

    public function sendResponse(
        $originalMsg,
        ResponseInterface $response
    ) : void {
        $newMsg = $this->convertToAMQPMessage($response);

        foreach ($originalMsg->get_properties() as $index => $property) {
            if ($index !== 'application_headers') {
                $newMsg->set($index, $property);
            }
        }

        try {
            $replyTo = $originalMsg->get('reply_to');
        } catch (\OutOfBoundsException $e) {
            throw new UnknownReplyTo($e);
        }

        $this->channel->basic_publish(
            $newMsg,
            '',
            $replyTo
        );
    }
}
