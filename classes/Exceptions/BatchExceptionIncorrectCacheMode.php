<?php

namespace Shasoft\Batch\Exceptions;

use Shasoft\Batch\BatchException;

// Исключение
class BatchExceptionIncorrectCacheMode extends BatchException
{
    // Конструктор
    public function __construct(protected mixed $mode)
    {
        parent::__construct(null, 'Неверный режим кэширования групповой функции [' . $mode . ']');
    }
}
