<?php

namespace Shasoft\Batch\Extension;

use Shasoft\Batch\BatchError;
use Shasoft\Batch\BatchGroupResults;

// Адаптер для работы с БД
abstract class BatchDatabaseAdapter
{
    // Адаптер
    static protected ?BatchDatabaseAdapter $adapter = null;
    static public function set(BatchDatabaseAdapter $adapter): void
    {
        self::$adapter = $adapter;
    }
    // Получить адаптер
    static public function get(): BatchDatabaseAdapter
    {
        return self::$adapter;
    }
    // Преобразовать имя таблицы
    public function getTabName(mixed $tabname): string
    {
        return $tabname;
    }
    // Выбрать по значению ключа
    abstract public function getForKeyValue(BatchGroupResults $results, string $tabname, array $fieldnames, array $keyValue): void;
    // Выбрать по значениям ключевого поля
    abstract public function getForKeyValues(BatchGroupResults $results, string $tabname, array $fieldnames, string $keyName, array $keyValues): void;
    // Обновить значения полей по ключу
    abstract public function putForKeyValues(BatchGroupResults $results, string $tabname, array $keyValue, array $values): void;
}
