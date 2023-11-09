<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\CacheDebug;

class CacheTest extends BaseCache
{
    public function testCache0()
    {
        // Простой тест
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnPut0($this->key, 13);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg): void {
                self::assertEquals($dbg->ret, 13);
            }
        ]);
    }
    public function testCache1()
    {
        // Простой тест
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet2($this->key);
            },
            function (CacheDebug $dbg): void {
                self::assertEquals($dbg->ret, 20);
            }
        ]);
    }
    public function testCache2()
    {
        // Тест с Put
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnPut2($this->key, 6);
            },
            function (CacheDebug $dbg) {
                self::assertEquals($dbg->fns->X[2][$this->key], 6);
                return $dbg->fns->fnGet2($this->key);
            },
            function (CacheDebug $dbg): void {
                self::assertEquals($dbg->ret, 12);
            }
        ]);
    }
    public function testCache3()
    {
        // Тест с Put
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnPut2($this->key, 6);
            },
            function (CacheDebug $dbg) {
                self::assertEquals($dbg->fns->X[2][$this->key], 6);
                return $dbg->fns->fnGet2($this->key);
            },
            function (CacheDebug $dbg) {
                // Изменим множитель
                $dbg->fns->X[2][$this->key] =  16;
                // И прочитаем значение. 
                return $dbg->fns->fnGet2($this->key);
            },
            function (CacheDebug $dbg): void {
                // так как множитель изменили в обход функции put, то старое значение должно взяться из КЕШ-а
                self::assertEquals($dbg->ret, 12);
            }

        ]);
    }
    public function testCache4()
    {
        // Тест с Put
        $this->runBatches([
            function (CacheDebug $dbg) {
                return BatchManager::all([$dbg->fns->fnPut1($this->key, 2), $dbg->fns->fnPut2($this->key, 3)]);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet2($this->key);
            },
            function (CacheDebug $dbg) {
                self::assertEquals($dbg->ret, $this->key * 3);
                return $dbg->fns->fnGet1($this->key);
            },
            function (CacheDebug $dbg): void {
                self::assertEquals($dbg->ret, $this->key * 3 + 2);
            }
        ]);
    }
    public function testCache5()
    {
        // Тест с Put
        $this->runBatches([
            function (CacheDebug $dbg) {
                return BatchManager::all([$dbg->fns->fnPut1($this->key, 2), $dbg->fns->fnPut2($this->key, 3)]);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet1($this->key);
            },
            function (CacheDebug $dbg): void {
                self::assertEquals($dbg->ret, $this->key * 3 + 2);
                //s_dump($dbg->cache);
                //
                $dbg
                    ->cache('"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnGet2",2')
                    ->value(6)
                    ->puts([
                        '"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut2",2' => "3"
                    ]);
                //
                $dbg
                    ->cache('"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnGet1",2')
                    ->value(8)
                    ->puts([
                        '"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut1",2' => "2",
                        '"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut2",2' => "3"
                    ]);
            }
        ], true);
    }
    public function testCache6()
    {
        // Тест с Put
        $this->runBatches([
            function (CacheDebug $dbg) {
                return BatchManager::all([$dbg->fns->fnPut1($this->key, 2), $dbg->fns->fnPut2($this->key, 3)]);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet2($this->key);
            },
            function (CacheDebug $dbg) {
                self::assertEquals($dbg->ret, $this->key * 3);
                return $dbg->fns->fnGet1($this->key);
            },
            function (CacheDebug $dbg): void {
                self::assertEquals($dbg->ret, $this->key * 3 + 2);
                //
                $dbg
                    ->cache('"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnGet2",2')
                    ->value(6)
                    ->puts([
                        '"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut2",2' => "3"
                    ]);
                //
                $dbg
                    ->cache('"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnGet1",2')
                    ->value(8)
                    ->puts([
                        '"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut1",2' => "2",
                        '"Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns->fnPut2",2' => "3"
                    ]);
            }
        ], true);
    }
    public function testCache7()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnNone1($this->key);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnNone1($this->key);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnNone1($this->key);
            },
            function (CacheDebug $dbg): void {
                self::assertEquals($dbg->fns->Count['fnGet2'], 1);
                self::assertEquals($dbg->fns->Count['fnNone1'], 3);
            }
        ]);
    }
}
