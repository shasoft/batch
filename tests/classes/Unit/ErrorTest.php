<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchError;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\ApiTest\BatchTestFns;

class ErrorTest extends Base
{
    //*
    public function testError1()
    {
        $ret = BatchManager::run(function () {
            return BatchTestFns::aaa(10)
                ->then(function ($value) {
                    return BatchError::create('test');
                });
        });
        self::assertTrue(
            BatchError::has($ret)
        );
    }
    public function testError2()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(10)->then(function ($value) {
                    return BatchError::create('test2');
                })
            ]);
        });
        self::assertValue(
            $ret,
            '[BatchError("test2")]'
        );
    }
    public function testError3()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(10)->then(function ($value) {
                    return BatchError::create('test3.1', 666);
                }),
                BatchTestFns::aaa(10)->then(function ($value) {
                    return BatchError::create('test3.2');
                })
            ]);
        });
        self::assertValue(
            $ret,
            '[BatchError("test3.1",666),BatchError("test3.2")]'
        );
    }
    //*/
}
