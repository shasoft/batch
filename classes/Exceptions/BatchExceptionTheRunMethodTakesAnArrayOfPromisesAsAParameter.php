<?php

namespace Shasoft\Batch\Exceptions;

use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchException;
use Shasoft\Batch\BatchManagerContext;

// Исключение
class BatchExceptionTheRunMethodTakesAnArrayOfPromisesAsAParameter extends BatchException
{
    // Конструктор
    public function __construct(BatchManagerContext $context)
    {
        parent::__construct($context, 'Метод ' . BatchManager::class . '::run принимает в качестве параметра массив обещаний ' . BatchPromise::class);
    }
}
