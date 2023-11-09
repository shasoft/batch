<?php

namespace Shasoft\Batch;

use Shasoft\Data\Key;
use Shasoft\Batch\BatchError;

// Результаты
class BatchGroupResults
{
    // Значения
    protected array $values = [];
    // Конструктор
    public function __construct()
    {
    }
    // Записать значение
    public function set(mixed $value, ...$args): void
    {
        $argsForKey = [];
        foreach ($args as $arg) {
            if ($arg instanceof BatchGroupArg) {
                $argsForKey[] = $arg->value();
            } else {
                $argsForKey[] = $arg;
            }
        }
        $key = Key::toKey($argsForKey);
        $this->values[$key] = $value;
    }
    // Читать значение
    public function get(...$args): mixed
    {
        $key = Key::toKey($args);
        //s_dump($args, $key);
        return $this->values[$key] ?? BatchError::createUndefined();
    }
}
