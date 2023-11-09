<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchDebug;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\ApiTest\BatchTestFns;

class PriorityTest extends Base
{
    public function testPriority1()
    {
        $log = new BatchDebug;
        BatchDebug::setDebug($log);
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa('AAA'),
                BatchTestFns::bbb('BBB'),
            ]);
        });
        self::assertValue(
            $log->getGroups(),
            '["Shasoft.Batch.Tests.ApiTest.BatchTestFns::bbb","Shasoft.Batch.Tests.ApiTest.BatchTestFns::aaa"]'
        );
        self::assertValue(
            $ret,
            '["aAAA","bBBB"]'
        );
    }
    public function testPriority2()
    {
        $log = new BatchDebug;
        BatchDebug::setDebug($log);
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa('AAA'),
                BatchTestFns::bbb('BBB'),
                BatchTestFns::aaa('AAA'),
                BatchTestFns::bbb('BBB'),
            ]);
        });
        self::assertValue(
            $log->getGroups(),
            '["Shasoft.Batch.Tests.ApiTest.BatchTestFns::bbb","Shasoft.Batch.Tests.ApiTest.BatchTestFns::aaa"]'
        );
        self::assertValue(
            $ret,
            '["aAAA","bBBB","aAAA","bBBB"]'
        );
    }
}
