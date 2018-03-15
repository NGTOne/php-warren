<?php

namespace Warren\Test;

use PHPUnit\Framework\TestCase;

use Warren\MiddlewareSet;

class MiddlewareSetTest extends TestCase
{
    public function setUp() : void
    {
        $this->set = new MiddlewareSet();
    }

    /**
     * @dataProvider middlewareProvider
     */
    public function testAddMiddleware($wares)
    {
        foreach ($wares as $ware) {
            $result = $this->set->addMiddleware($ware);
            $this->assertSame($this->set, $result);
        }

        $this->assertCount(count($wares), $this->set);
    }

    public function middlewareProvider()
    {
        return [
            [[]],
            [[function (
                RequestInterface $req,
                ResponseInterface $res
            ) {}]],
            [
                [
                    function (
                        RequestInterface $req,
                        ResponseInterface $res
                    ) {},
                    function (
                        RequestInterface $req,
                        ResponseInterface $res
                    ) {}
                ]
            ]
        ];
    }

    /**
     * @dataProvider middlewareProvider
     */
    public function testClone($wares)
    {
        foreach ($wares as $ware) {
            $this->set->addMiddleware($ware);
        }

        $result = $this->set->clone();
        $result->addMiddleware(function (
            RequestInterface $req,
            ResponseInterface $res
        ) {});

        $this->assertCount(count($wares), $this->set);
        $this->assertCount(count($wares) + 1, $result);

        $this->assertNotSame($this->set, $result);
    }
}
