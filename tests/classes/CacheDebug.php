<?php

namespace Shasoft\Batch\Tests;

use Shasoft\Batch\BatchUtil;
use Shasoft\Batch\BatchDebug;
use Shasoft\Batch\BatchConfig;
use PHPUnit\Framework\TestCase;
use Shasoft\Batch\BatchManager;
use Shasoft\PsrCache\CacheItemPool;
use Shasoft\Batch\Tests\CacheAssert;
use Shasoft\PsrCache\Adapter\CacheAdapter;
use Shasoft\PsrCache\Adapter\CacheAdapterArray;
use Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns;


class CacheDebug
{
    public BatchTestCacheFns $fns;
    public CacheAdapterArray $adapter;
    public CacheItemPool $cache;
    public BatchDebug $debug;
    public mixed $ret;
    // Конструктор
    public function __construct()
    {
        // Функции
        $this->fns = new BatchTestCacheFns();
        // КЕШ
        $this->adapter = new CacheAdapterArray;
        $this->cache = new CacheItemPool($this->adapter);
        BatchConfig::setICache($this->cache);
        //
        $this->ret = null;
    }
    // Конструктор
    public function run(array $calls, bool $hasBrowse, bool $hasDbgArgsKeyGenerate)
    {
        if ($hasBrowse) {
            s_dump($this->adapter->all());
        }
        if ($hasDbgArgsKeyGenerate) {
            BatchConfig::setFnArgsKeyGenerate(BatchDebug::fnArgsKeyGenerate());
        }
        foreach ($calls as $call) {
            if ($hasBrowse) {
                echo '<div style="border: dashed DarkTurquoise;">';
            }
            // Создать лог
            $this->debug = new BatchDebug;
            // Обнулить контекст чтобы каждый тест начинался "с нуля"
            BatchUtil::clearManagerContext();
            //
            BatchDebug::setDebug($this->debug);
            //
            $refCall = new \ReflectionFunction($call);
            $type = (string)$refCall->getReturnType();
            if ($type == 'void') {
                $call($this);
            } else {
                // Выполнить
                $this->ret = BatchManager::run(
                    function () use ($call) {
                        return $call($this);
                    }
                );
            }
            if ($hasBrowse) {
                $html = $this->debug->getHtmlLog();
                $html = str_replace(BatchTestCacheFns::class, 'BatchTestCacheFns', $html);
                echo $html . "</div>";
                //echo var_export($cache->all());
                s_dump($this->adapter->all(), $this);
            }
        }
        if ($hasDbgArgsKeyGenerate) {
            BatchConfig::setFnArgsKeyGenerate(null);
        }
    }
    // Проверить наличие данных в КЭШ
    public function cache(string $key): CacheAssert
    {
        //
        TestCase::assertArrayHasKey($key, $this->adapter->all(), 'В КЭШе отсутствует значение с ключом ' . $key);
        //s_dump($this->adapter->all());
        //
        return new CacheAssert($key, $this->cache->getItem($key)->get());
    }
}
