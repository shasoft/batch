<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\Tests\CacheDebug;

class CacheModeTest extends BaseCache
{
    public function testOff()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Очистить КЭШ
                $dbg->adapter->clear();
                // Чтение напрямую
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_OFF);
            },
            function (CacheDebug $dbg): void {
                // КЭШ должен быть полностью пуст
                $this->assertCache0($dbg, false, false, 0);
                // Функция вызвалась ДВА раза
                self::assertEquals(2, $dbg->fns->Count['fnGet0']);
            }
        ], true);
    }
    public function testDirect()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Очистить КЭШ
                $dbg->adapter->clear();
                // Чтение напрямую
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_DIRECT);
            },
            function (CacheDebug $dbg): void {
                //s_dump($dbg->fns->Count, $dbg->adapter->all());
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Функция вызвалась ДВА раза
                self::assertEquals(2, $dbg->fns->Count['fnGet0']);
            }
        ], true);
    }
    public function testDirect1()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Чтение напрямую
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_DIRECT);
            },
            function (CacheDebug $dbg): void {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Функция вызвалась ДВА раза
                self::assertEquals(2, $dbg->fns->Count['fnGet0']);
            }
        ], true);
    }
    public function testOn()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Чтение из КЭШа
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_ON);
            },
            function (CacheDebug $dbg): void {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Функция вызвалась ОДИН раз
                self::assertEquals(1, $dbg->fns->Count['fnGet0']);
            }
        ], true);
    }
    public function testOn1()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Очистить КЭШ
                $dbg->adapter->clear();
                // Чтение из КЭШа
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_ON);
            },
            function (CacheDebug $dbg): void {
                // КЭШ должен быть полностью заполнен
                $this->assertCache0($dbg, true, true, 0);
                // Функция вызвалась ДВА раза
                self::assertEquals(2, $dbg->fns->Count['fnGet0']);
            }
        ], true);
    }
    public function testOffTreeCall()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_OFF);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_OFF);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_OFF);
            },
            function (CacheDebug $dbg): void {
                // Функция вызвалась ТРИ раза
                self::assertEquals(3, $dbg->fns->Count['fnGet0']);
            }
        ]);
    }
    public function testOffTwoCall1()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_OFF);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg): void {
                // Функция вызвалась ДВА раза
                self::assertEquals(2, $dbg->fns->Count['fnGet0']);
            }
        ]);
    }
    public function testOffTwoCall2()
    {
        $this->runBatches([
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key);
            },
            function (CacheDebug $dbg) {
                return $dbg->fns->fnGet0($this->key)->setCacheMode(BatchPromise::MODE_CACHE_OFF);
            },
            function (CacheDebug $dbg): void {
                // Функция вызвалась ДВА раза
                self::assertEquals(2, $dbg->fns->Count['fnGet0']);
            }
        ]);
    }
}
