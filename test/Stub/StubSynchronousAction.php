<?php

namespace Warren\Test\Stub;

use Warren\SynchronousAction;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Simple stub class that pretends to perform an action of some sort,
// records what it got, then sends a canned response
class StubSynchronousAction implements SynchronousAction
{
    public $reqBody;
    public $reqHeaders;
    public $resBody;
    public $resHeaders;

    private $newHeaders;
    private $newBody;

    public function __construct($body, $headers)
    {
        $this->newBody = $body;
        $this->newHeaders = $headers;
    }

    public function performSynchronousAction(
        RequestInterface $req,
        ResponseInterface $res
    ) : ResponseInterface {
        $this->reqBody = (string)$req->getBody();
        $this->reqHeaders = $req->getHeaders();
        $this->resBody = (string)$res->getBody();
        $this->resHeaders = $res->getHeaders();

        foreach ($this->newHeaders as $header => $value) {
            $res = $res->withHeader($header, $value);
        }

        return $res->withBody(\GuzzleHttp\Psr7\stream_for(
            $this->newBody
        ));
    }
}
