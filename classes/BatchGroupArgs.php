<?php

namespace Shasoft\Batch;

use Shasoft\Data\Key;

// Аргументы
class BatchGroupArgs
{
    // Конструктор
    public function __construct(protected array $args)
    {
    }
    // Перебрать все значения
    public function each(callable $cb): void
    {
        foreach ($this->args as $args) {
            call_user_func_array($cb, $args);
        }
    }
    // Группировать
    public function groupBy(callable $cb, int|callable $index = 0): void
    {
        $groups = [];
        foreach ($this->args as $args) {
            $hasCallable = is_callable($index);
            // Получить значение
            if ($hasCallable) {
                $value = $index(...$args);
            } else {
                $value = $args[$index];
            }
            // Сгенерировать ключ по значению
            $keyValue = Key::toKey($value);
            // Если такой группы нет
            if (!array_key_exists($keyValue, $groups)) {
                // Добавить группу для ключа
                $groups[$keyValue] = [
                    'arg' => new BatchGroupArg($value, $keyValue),
                    'args' => []
                ];
            }

            if ($hasCallable) {
                // Параметры
                $groups[$keyValue]['args'][] = $args;
            } else {
                // Удалить из списка параметр, по которому группируют
                unset($args[$index]);
                // Добавить оставшиеся параметры
                $groups[$keyValue]['args'][] = array_values($args);
            }
        }
        //s_dump($this->args, $groups);
        foreach ($groups as $keyValue => $group) {
            $cb($group['arg'], new self($group['args']));
        }
    }
    // Список значений аргумента
    public function values(int $index, bool $unique = true): array
    {
        $ret = [];
        foreach ($this->args as $args) {
            $ret[] = $args[$index];
        }
        if ($unique) {
            $values = array_unique(array_map(function ($value) {
                return Key::toKey($value);
            }, $ret), SORT_STRING);
            $ret = [];
            foreach ($values as $value) {
                $ret[] = Key::toValue($value);
            }
        }
        return $ret;
    }
}
