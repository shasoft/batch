<?php

namespace Shasoft\Batch\Exceptions;

use Shasoft\CDump\CDumpHtml;
use Shasoft\Batch\BatchException;
use Shasoft\Batch\BatchPromiseContext;

// Исключение
class BatchExceptionPromiseAlreadyFulfilled extends BatchException
{
    // Конструктор
    public function __construct(protected BatchPromiseContext $promiseContext)
    {
        parent::__construct($promiseContext->managerContext, 'Обещание ' . CDumpHtml::toString($promiseContext) . ' уже выполнено');
    }
}
