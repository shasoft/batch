<?php

namespace Shasoft\Batch\Tests\ApiTest;

use Shasoft\Batch\BatchGroup;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchGroupConfig;

// Пример функции Get и Put
class ExampleCacheGetPut
{
    // Хранение значений
    static protected array $data = [];
    // Функция вида Get
    static public function fnGet(int $x): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            // Установить тип функции = Get
            $groupConfig->setCacheGet();
        }, function (BatchGroup $group) {
            // Функция получения результата для каждого набора аргументов
            $group->setResult(function (int $x) {
                // Читать текущее значение 
                $ret = 2 * (self::$data[$x] ?? 0);
                // Установить зависимость от функции вида Put   
                self::fnPut($x, $ret);
                // Вернуть результат
                return $ret;
            });
        }, $x);
    }
    // Функция вида Put
    public function fnPut(int $x, int $value): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            // Установить тип функции = Put
            // Указать список индексов ключевых аргументов
            $groupConfig->setCachePut(0);
        }, function (BatchGroup $group) {
            // Функция получения результата для каждого набора аргументов
            $group->setResult(function (int $x, int $value): void {
                // Установить значение
                self::$data[$x] = $value;
            });
        }, $x, $value);
    }
}
