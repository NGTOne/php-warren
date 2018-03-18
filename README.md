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
function(RequestInterface $req, ResponseInterface $res) : ResponseInterface {
    // Magic
}
```
idiom, with different middleware stacks for synchronous and asynchronous
calls.

To this end, it provides an abstraction layer over the queue
implementation, which converts the queue-specific message into a PSR7
object. Once the message has been successfully processed, it then goes
the other way - converting the PSR7 response object back into the queue
service's own internal representation.

## Getting Started with Warren
To create a Warren-based service worker, you need to implement at least
one of `Warren\SynchronousAction` or `Warren\AsynchronousAction`. Then,
just plug it (or them) into Warren using the fluent interface:
```php
// Using the PhpAmqpLib connection adapter - others will be available soon
$rabbitConn = new AMQPStreamConnection('server', 5672, 'user', 'pass');
$conn = new Warren\Connection\PhpAmqpLibConnection(
    $rabbitConn->channel(),
    'incoming_msg_queue'
);

$warren = new Warren\RabbitConsumer($conn);

$warren->addAsynchronousAction(new MyAwesomeAction, 'my_awesome_action')
    ->addSynchronousAction(new MySynchronousAction, 'my_sync_action')
    ->listen();
```

You can add PSR7 middleware to a Warren worker like so:
```php
$warren
    ->addAsynchronousAction(new MyAwesomeAction, 'my_awesome_action')
    ->addAsynchronousMiddleware(function ($req, $res) {
        return $res->withBody('This is so cool!');
    })
    ->listen();
```
