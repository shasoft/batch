<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpTrace;
use Psr\Cache\CacheItemPoolInterface;


// Настройки
class BatchConfig
{
    // Установить функцию для генерации ключа группировки
    static ?\Closure $fnBatchKeyGenerate = null;
    static public function setFnBatchKeyGenerate(callable $callback): void
    {
        self::$fnBatchKeyGenerate = $callback;
    }
    // Сгенерировать ключ
    static public function getBatchKey(callable $fnExecuter): string
    {
        if (is_callable(self::$fnBatchKeyGenerate)) {
            $fn = self::$fnBatchKeyGenerate;
            return $fn($fnExecuter);
        }
        // Определить место вызова
        $oTrace = new CDumpTrace(16, DEBUG_BACKTRACE_IGNORE_ARGS);
        $index = $oTrace->findNameFirst(BatchManager::class . '::createEx');
        if ($oTrace->name($index + 1) == BatchManager::class . '::create') {
            $index++;
        }
        $trace = $oTrace->get($index + 1);
        // А может это анонимная функция?
        if (strpos($trace['function'] ?? '', '{closure}') !== false) {
            // Это анонимная функция: ключ на основе имени файла и строки
            $index--;
            $trace = $oTrace->get($index);
            $file = $trace['file'];
            $key = strtoupper(hash('crc32', $file))  . '/' . substr(basename($file, pathinfo($file, PATHINFO_EXTENSION)), 0, -1) . ':' . $trace['line'];
        } else {
            // Это функция/метод класса
            $key = CDumpTrace::getName($trace);
        }
        return $key;
    }
    // Установить функцию для генерации ключа аргументов
    static ?\Closure $fnArgsKeyGenerate = null;
    static public function setFnArgsKeyGenerate(?callable $callback): void
    {
        self::$fnArgsKeyGenerate = $callback;
    }
    // Сгенерировать ключ аргументов
    static public function getArgsKey(array $args): string
    {
        if (is_callable(self::$fnArgsKeyGenerate)) {
            $fn = self::$fnArgsKeyGenerate;
            return $fn($args);
        }
        //return '#' . json_encode($args);
        return hash('md5', serialize($args));
    }
    // Установить глобальные интерфейсы КЭШа 
    static protected CacheItemPoolInterface|\Closure|null $cacheGet = null; // GET
    static protected CacheItemPoolInterface|\Closure|null $cachePut = null; // PUT
    static public function setICache(CacheItemPoolInterface|callable|null $cacheGet, CacheItemPoolInterface|callable|null $cachePut = null): void
    {
        self::$cacheGet = $cacheGet;
        if (is_null($cachePut)) {
            if (is_callable($cacheGet)) {
                self::$cachePut = function () {
                    return self::getICacheGet();
                };
            } else {
                self::$cachePut = $cacheGet;
            }
        } else {
            self::$cachePut = $cachePut;
        }
    }
    static public function getICacheGet(): ?CacheItemPoolInterface
    {
        if (is_callable(self::$cacheGet)) {
            self::$cacheGet = call_user_func(self::$cacheGet);
        }
        return self::$cacheGet;
    }
    static public function getICachePut(): ?CacheItemPoolInterface
    {
        if (is_callable(self::$cachePut)) {
            self::$cachePut = call_user_func(self::$cachePut);
        }
        return self::$cachePut;
    }
}
