<?php

namespace Warren\Test;

use PHPUnit\Framework\TestCase;

use Warren\MiddlewareSet;

use Warren\PSR\RabbitMQRequest;
use Warren\PSR\RabbitMQResponse;

class MiddlewareSetTest extends TestCase
{
    public function setUp() : void
    {
        $this->set = new MiddlewareSet();
    }

    /**
     * @dataProvider getMiddlewareStackProvider
     */
    public function testGetMiddlewareStack($wares, $expected)
    {
        $req = new RabbitMQRequest;
        $res = new RabbitMQResponse;

        foreach ($wares as $ware) {
            $result = $this->set->addMiddleware($ware);
            $this->assertSame($this->set, $result);
        }

        $stack = $this->set->getMiddlewareStack();

        $this->assertEquals(
            $expected,
            (string)($stack($req, $res)->getBody())
        );
    }

    public function getMiddlewareStackProvider()
    {
        return [
            [
                [function ($req, $res, $next = null) {
                    return $res->withBody(
                        \GuzzleHttp\Psr7\stream_for(
                            "FOO".$req->getBody()."BAR"
                        )
                    );
                }],
                "FOOBAR"
            ], [
                [
                    function ($req, $res, $next = null) {
                        return $res->withBody(
                            \GuzzleHttp\Psr7\stream_for(
                                "BAZ".$next($res, $req)->getBody()."QUX"
                            )
                        );
                    },
                    function ($req, $res, $next = null) {
                        return $res->withBody(
                            \GuzzleHttp\Psr7\stream_for(
                                "FOO".$req->getBody()."BAR"
                            )
                        );
                    }
                ],
                "BAZFOOBARQUX"
            ], [
                [
                    function ($req, $res, $next = null) {
                        return $res->withBody(
                            \GuzzleHttp\Psr7\stream_for(
                                "ANOTHER".
                                    $next($res, $req)->getBody().
                                    "ONE"
                            )
                        );
                    },
                    function ($req, $res, $next = null) {
                        return $res->withBody(
                            \GuzzleHttp\Psr7\stream_for(
                                "BAZ".$next($res, $req)->getBody()."QUX"
                            )
                        );
                    },
                    function ($req, $res, $next = null) {
                        return $res->withBody(
                            \GuzzleHttp\Psr7\stream_for(
                                "FOO".$req->getBody()."BAR"
                            )
                        );
                    }
                ],
                "ANOTHERBAZFOOBARQUXONE"
            ]
        ];
    }
}
