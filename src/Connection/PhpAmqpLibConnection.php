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

    private $headerProperties = [];

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

        foreach ($this->headerProperties as $property => $header) {
            try {
                $headers[$header] = $msg->get($property);
            } catch (\OutOfBoundsException $e) {
                // Property isn't present, nothing to do here
            }
        }

        return new RabbitMQRequest(
            $headers,
            $msg->getBody()
        );
    }

    public function setHeaderProperties(array $mappings)
    {
        foreach ($mappings as $property => $header) {
            if (!is_string($property) or empty($property)) {
                throw new \TypeError(
                    "All RabbitMQ properties are named using strings"
                );
            }

            if (!is_string($header)) {
                throw new \TypeError(
                    "Attempting to map $property to a non-string header"
                );
            }
        }

        // Since we map the application headers anyways
        unset($mappings['application_headers']);

        $this->headerProperties = $mappings;
        return $this;
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
        $properties = [];
        foreach ($this->headerProperties as $property => $header) {
            $properties[$property] = $response->getHeaderLine($header);
            $response = $response->withoutHeader($header);
        }

        $properties['application_headers'] = new AMQPTable(
            $response->getHeaders()
        );

        return new AMQPMessage((string)$response->getBody(), $properties);
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
