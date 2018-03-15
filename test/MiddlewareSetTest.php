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
     * @dataProvider addMiddlewareProvider
     */
    public function testAddMiddleware($wares, $expectedCount)
    {
        foreach ($wares as $ware) {
            $this->set->addMiddleware($ware);
        }

        $this->assertCount($expectedCount, $this->set);
    }

    public function addMiddlewareProvider()
    {
        return [
            [[], 0],
            [[function (
                RequestInterface $req,
                ResponseInterface $res
            ) {}], 1],
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
                ],
                2
            ]
        ];
    }
}
