<?php

namespace Shasoft\Batch;

// Аргумент
class BatchGroupArg
{
    // Конструктор
    public function __construct(protected mixed $value, protected string $key)
    {
    }
    // Значение
    public function value(): mixed
    {
        return $this->value;
    }
    // Ключ
    public function key(): string
    {
        return $this->key;
    }
}
