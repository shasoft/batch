<?php

namespace Shasoft\Batch\Exceptions;

use Shasoft\Batch\BatchException;
use Shasoft\Batch\BatchPromiseContext;

// Исключение
class BatchExceptionTheResultOfTheAllFunctionMustBeAnArray extends BatchException
{
    // Конструктор
    public function __construct(protected BatchPromiseContext $promiseContext)
    {
        parent::__construct($promiseContext->managerContext, 'Результат выполнения функции All должен быть массив');
    }
}
