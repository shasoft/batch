<?php

namespace Shasoft\Batch\Extension;

use Shasoft\Batch\BatchError;
use Shasoft\Batch\BatchGroup;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchGroupArg;
use Shasoft\Batch\BatchGroupArgs;
use Shasoft\Batch\BatchGroupConfig;
use Shasoft\Batch\BatchGroupResults;
use Shasoft\Batch\Extension\BatchDatabaseAdapter;

// Групповые функции работы с БД
class BatchDatabase
{
    // Читать значение поля таблицы с КЕШированием результата
    static public function get(mixed $tabname, array $keys, string $fieldname): BatchPromise
    {
        // Адаптер
        $adapter = BatchDatabaseAdapter::get();
        //
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig): void {
            // Установить тип функции = GET
            $groupConfig->setCacheGet();
        }, function (BatchGroup $group) use ($adapter): void {
            // Группировка аргументов и выполнение запросов
            $results = new BatchGroupResults;
            $group->args()->groupBy(function (BatchGroupArg $tabname, BatchGroupArgs $args) use ($results, $adapter): void {
                //
                $args->groupBy(function (BatchGroupArg $keys, BatchGroupArgs $args) use ($tabname, $results, $adapter) {
                    $keyName = $keys->value();
                    if (empty($keyName)) {
                        // Несколько ключевых полей
                        // Группируем по значениям ключевых полей
                        $args->groupBy(function (BatchGroupArg $keys, BatchGroupArgs $args) use ($tabname, $results, $adapter) {
                            // Вызвать обработчик
                            $adapter->getForKeyValue(
                                $results,                // Объект для записи результата
                                $tabname->value(),       // Таблица
                                $args->values(0),        // Список полей для выборки
                                $keys->value()           // Ключ = [поле=>значение, поле=>значение, ...]
                            );
                        });
                    } else {
                        // Список идентификаторов
                        $keyValues = [];
                        foreach ($args->values(0) as $argKeys) {
                            $keyValues[$argKeys[$keyName]] = 1;
                        }
                        // Вызвать обработчик
                        $adapter->getForKeyValues(
                            $results,                     // Объект для записи результата
                            $tabname->value(),            // Таблица
                            $args->values(1),             // Список полей для выборки
                            $keyName,                     // Имя ключевого поля
                            array_keys($keyValues)        // Список уникальных значений ключевого поля [v1, v2, ...]
                        );
                    }
                }, function (array $keys, string $fieldname) {
                    // Определим список ключевых полей
                    $fieldKeys = array_keys($keys);
                    // Если только одно ключевое поле
                    if (count($fieldKeys) == 1) {
                        // Группируем по полю ключа
                        return $fieldKeys[0];
                    } else {
                        // Группируем по пустому значению
                        return '';
                    }
                });
                //s_dump($tabname, $args);
            });
            //s_dump($results);            
            // Установить результаты
            $group->setResult(function (string $tabname, array $keys, string $fieldname) use ($results) {
                // Получить значение
                $value =  $results->get($tabname, $keys, $fieldname);
                // Установить привязку
                self::put($tabname, $keys, $fieldname, $value);
                // Вернуть результат
                return $value;
            });
            //
        }, $adapter->getTabName($tabname), $keys, $fieldname);
    }
    // Установить значение поля таблицы
    static public function put(mixed $tabname, array $keys, string $fieldname, mixed $value): BatchPromise
    {
        // Адаптер
        $adapter = BatchDatabaseAdapter::get();
        //
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            // Установить низкий приоритет чтобы эти функции выполнялись в последнюю очередь
            $groupConfig->setPriority(BatchManager::PRIORITY_LOW);
            // Указать тип функции (индексы входных параметров)
            $groupConfig->setCachePut(0, 1, 2);
        }, function (BatchGroup $group) use ($adapter): void {
            // Группировка аргументов и выполнение запросов
            $results = new BatchGroupResults;
            $group->args()->groupBy(function (BatchGroupArg $tabname, BatchGroupArgs $args) use ($results, $adapter): void {
                //
                $args->groupBy(function (BatchGroupArg $keys, BatchGroupArgs $args) use ($tabname, $results, $adapter) {
                    // Получить значения полей
                    $values = [];
                    $args->each(function (string $fieldname, mixed $value) use (&$values) {
                        $values[$fieldname] = $value;
                    });
                    // Обновить значения полей по ключу
                    $adapter->putForKeyValues($results, $tabname->value(), $keys->value(), $values);
                });
            });
            // Установить результаты
            $group->setResult($results);
        }, $adapter->getTabName($tabname), $keys, $fieldname, $value);
    }
    // Читать значение поля таблицы по идентификатору с КЕШированием результата
    static public function getById(mixed $tabname, mixed $id, string $fieldname): BatchPromise
    {
        return self::get($tabname, ['id' => $id], $fieldname);
    }
    // Читать значение полей таблицы по идентификатору с КЕШированием результата
    static public function getsById(mixed $tabname, mixed $id, array $fieldnames): BatchPromise
    {
        // Преобразовать список полей в список обещаний
        $promises = array_map(function (string $fieldname) use ($tabname, $id) {
            return self::getById($tabname, $id, $fieldname);
        }, $fieldnames);
        // Ждать завершение обещаний
        return BatchManager::all($promises);
    }
    // Читать значение полей таблицы по идентификаторам с КЕШированием результата
    static public function getsByIds(mixed $tabname, array $ids, array $fieldnames): BatchPromise
    {
        $promises = [];
        foreach ($ids as $id) {
            $promises[$id] = self::getsById($tabname, $id, $fieldnames);
        }
        // Ждать завершение обещаний
        return BatchManager::all($promises)->then(function ($values) {
            $ret = [];
            foreach ($values as $id => $vals) {
                if (BatchError::hasErrors($vals)) {
                    $vals  = BatchError::create('Нет значений');
                }
                $ret[$id] = $vals;
            }
            return $ret;
        });
    }
}
