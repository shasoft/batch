<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\ApiTest\BatchTestFns;

class MainTest extends Base
{
    //*
    public function testOne()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::aaa(1);
        });
        self::assertValue($ret, 'a1');
    }
    public function testCascade1()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::aaa(1)->then(function (mixed $ret) {
                return BatchTestFns::aaa('2' . $ret);
            });
        });
        self::assertValue($ret, 'a2a1');
    }
    public function testCascade2()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::aaa(1)->then(function (mixed $ret) {
                return BatchTestFns::aaa('2' . $ret)->then(function (mixed $ret) {
                    return BatchTestFns::aaa('3' . $ret);
                });
            });
        });
        self::assertValue($ret, 'a3a2a1');
    }
    public function testChain()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::aaa(1)
                ->then(function ($ret) {
                    return BatchTestFns::aaa('2' . $ret);
                })
                ->then(function ($ret) {
                    return BatchTestFns::aaa('3' . $ret);
                });
        });
        self::assertValue($ret, 'a3a2a1');
    }
    public function testChainChain()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::aaa(1)
                ->then(function ($ret) {
                    return BatchTestFns::aaa('A' . $ret)
                        ->then(function ($ret) {
                            return BatchTestFns::bbb('B' . $ret);
                        })
                        ->then(function ($ret) {
                            return BatchTestFns::bbb('C' . $ret);
                        });
                })
                ->then(function ($ret) {
                    return BatchTestFns::aaa('D' . $ret);
                });
        });
        self::assertValue(
            $ret,
            'aDbCbBaAa1'
        );
    }
    public function testChainAllEmpty()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::aaa(1)
                ->then(function ($ret) {
                    return BatchManager::all([]);
                });
        });
        self::assertValue(
            $ret,
            '[]'
        );
    }
    public function testChainAllEmpty2()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([BatchTestFns::aaa(1)
                ->then(function ($ret) {
                    return BatchTestFns::aaa(2)
                        ->then(function () {
                            return 123;
                        })
                        ->then(function ($ret) {
                            return BatchManager::all([]);
                        });
                })]);
        });
        self::assertValue(
            $ret,
            '[[]]'
        );
    }

    public function testChainFromSetResult()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::xxx(10);
        });
        self::assertValue(
            $ret,
            'ax(10)'
        );
    }
    public function testOneToMany()
    {
        $ret = BatchManager::run(function () {
            $val = BatchTestFns::aaa('Z');
            return BatchManager::all([$val, $val]);
        });
        self::assertValue(
            $ret,
            '["aZ","aZ"]'
        );
    }
    public function testOneToMany2()
    {
        $ret = BatchManager::run(function () {
            $val = BatchTestFns::aaa('Z');
            return BatchManager::all(
                [
                    BatchManager::all([$val]),
                    'two' => BatchManager::all([$val])
                ]
            );
        });
        self::assertValue(
            $ret,
            '[0=>["aZ"],"two"=>["aZ"]]'
        );
    }
    //*/
}
