<?php

namespace Warren\Test\Stub;

use Warren\AsynchronousAction;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Simple stub class that pretends to perform an action of some sort,
// but really just records everything it got
class StubAsynchronousAction implements AsynchronousAction
{
    public $reqBody;
    public $reqHeaders;
    public $resBody;
    public $resHeaders;

    public function performAsynchronousAction(
        RequestInterface $req,
        ResponseInterface $res
    ) : void {
        $this->reqBody = (string)$req->getBody();
        $this->reqHeaders = $req->getHeaders();
        $this->resBody = (string)$res->getBody();
        $this->resHeaders = $res->getHeaders();
    }
}
