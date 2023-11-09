<?php

namespace Shasoft\Batch\Tests\ApiTest;

use Shasoft\Batch\BatchGroup;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchGroupConfig;

class ExampleCacheLifeTime
{
    // Функция вида LifeTime - кэширование на время
    static public function fnLifeTime(int $x): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            // Установить тип функции = LifeTime, установить время жизни = 5 минут
            $groupConfig->setCacheLifetime(5 * 60);
        }, function (BatchGroup $group) {
            // Функция получения результата для каждого набора аргументов
            $group->setResult(function (int $minValue) {
                // Вернуть случайное число от $minValue до 100
                // и кэшировать это значение на 5 минут
                return random_int(min($minValue, 100), 100);
            });
        }, $x);
    }
}
