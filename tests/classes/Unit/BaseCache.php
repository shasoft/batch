<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchDebug;
use Shasoft\Batch\Tests\CacheDebug;

class BaseCache extends Base
{
    protected int $key = 2;
    // BatchConfig::setICache($this->cache); 
    // Выполнить пакетные функции
    protected function runBatches(array $calls, bool $hasDbgArgsKeyGenerate = false)
    {
        $cacheDebug = new CacheDebug;
        $cacheDebug->run($calls, false, $hasDbgArgsKeyGenerate);
    }
    // Проверка значения КЭШа
    protected function assertCache0(CacheDebug $dbg, bool $fnGet0, bool $fnPut0, mixed $value)
    {
        $count = 0;
        if ($fnGet0) $count++;
        if ($fnPut0) $count++;
        self::assertCount($count, $dbg->adapter->all(), 'КЭШ содержит неверное количество элементов');
        //
        $cb = BatchDebug::fnArgsKeyGenerate();
        $putValue = $cb([$value]);
        //
        if ($fnGet0) {
            $dbg
                ->cache('"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnGet0",' . $this->key)
                ->value($value)
                ->puts([
                    '"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut0",' . $this->key => $putValue
                ]);
        }
        //
        if ($fnPut0) {
            $dbg
                ->cache('"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut0",' . $this->key)
                ->val($putValue);
        }
    }
}
