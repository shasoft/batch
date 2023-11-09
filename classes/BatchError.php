<?php

namespace Shasoft\Batch;


// Работа с ошибками
class BatchError
{
    protected function __construct(protected string $message, protected ?int $code = null)
    {
    }
    // Получить сообщение об ошибке
    public function message(): string
    {
        return $this->message;
    }
    // Получить код ошибки
    public function code(): ?int
    {
        return $this->code;
    }
    // Указан код ошибки?
    public function hasCode(): bool
    {
        return !is_null($this->code);
    }
    // Получить объект как строку
    public function __toString(): string
    {
        $ret = 'BatchError(' . str_replace("'", '"', var_export(str_replace("\n", "\\n", $this->message), true));
        if ($this->hasCode($this->code)) {
            $ret .= ',' . $this->code;
        }
        $ret .= ')';
        return $ret;
    }
    // Создать ошибку
    static public function create(string $message, ?int $code = null): static
    {
        return new static($message, $code);
    }
    // Создать ошибку отсутствия значения
    static public function createUndefined(): static
    {
        return new static('Значение не определено', 666);
    }
    // Значение является ошибкой?
    static public function has(mixed $value): bool
    {
        return is_object($value) && $value instanceof static;
    }
    // Все значения массив являются ошибками?
    static public function hasErrors(array $values): bool
    {
        $values = array_filter($values, function ($value) {
            return !self::has($value);
        });
        return empty($values);
    }
    // Отфильтровать массив значений удалив: true - ошибки/ false - не ошибки
    static public function filter(array $values, bool $removeError = true): array
    {
        return array_filter($values, function (mixed $value) use ($removeError) {
            return $removeError != self::has($value);
        });
    }
    // Заполнить ошибки значениями
    static public function fill(array $values, mixed $value): array
    {
        array_walk($values, function ($value, $key, $arg) {
            if (self::has($value)) {
                if (is_callable($arg)) {
                    return $arg($value, $key);
                }
                return $arg;
            }
            return $value;
        }, $value);
        return $values;
    }
}
