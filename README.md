# Warren

Warren is a lightweight PHP library that enables quick and easy creation of
both synchronous and asynchronous RabbitMQ message consumers (the "sub" part
of "pub/sub").

## Features
Warren is designed with two primary goals in mind:

1. Easy, testable, object-oriented creation of service workers that are
    capable of undertaking multiple _different_ actions, both synchronous
    and asynchronous.
2. Easy use of PSR7 middleware using the
```php
function (
    RequestInterface $req,
    ResponseInterface $res,
    callable $next = null
) : ResponseInterface
```
idiom, with different middleware stacks for synchronous and asynchronous
calls.

To this end, it provides an abstraction layer over the queue
implementation, which converts the queue-specific message into a PSR7
object. Once the message has been successfully processed, it then goes
the other way - converting the PSR7 response object back into the queue
service's own internal representation.

## Installation
Installation is simple - just use [Composer](https://getcomposer.org/):

    composer require warren/warren

## Dependencies
Warren requires PHP 7.1 or higher. In addition, you will need to install at
least one of the following libraries in order to use the corresponding queue
bindings:

- [php-amqplib](https://github.com/php-amqplib/php-amqplib)

## Getting Started with Warren
To create a Warren-based service worker, you need to implement at least
one of `Warren\SynchronousAction` or `Warren\AsynchronousAction`. Then,
just plug it (or them) into Warren using the fluent interface:
```php
// Using the PhpAmqpLib connection adapter - others will be available soon
$rabbitConn = new AMQPStreamConnection('server', 5672, 'user', 'pass');
$channel = $rabbitConn->channel();
$channel->queue_declare('incoming_msg_queue');

$conn = new Warren\Connection\PhpAmqpLibConnection(
    $channel,
    'incoming_msg_queue'
);

$warren = new Warren\RabbitConsumer($conn);

$warren->addAsynchronousAction(new MyAwesomeAction, 'my_awesome_action')
    ->addSynchronousAction(new MySynchronousAction, 'my_sync_action')
    ->listen();
```

Each call corresponds to a separate action, not unlike an endpoint for an HTTP
API. The message header to use to determine which action to take can be set
with:

```php
$warren->setActionHeader('my_header');
```

You can add PSR7 middleware to a Warren worker like so:
```php
$warren
    ->addAsynchronousAction(new MyAwesomeAction, 'my_awesome_action')
    ->addAsynchronousMiddleware(function ($req, $res, $next) {
        return $next(
            $req,
            $res->withHeader('this', 'is so cool!')
        );
    })
    ->listen();
```

Synchronous and asynchronous actions have separate middleware stacks. Note
that any response values for asynchronous actions _will_ be ignored.
