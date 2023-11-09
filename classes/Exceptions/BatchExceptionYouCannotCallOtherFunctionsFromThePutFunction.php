<?php

namespace Shasoft\Batch\Exceptions;

use Shasoft\Batch\BatchException;
use Shasoft\Batch\BatchPromiseContext;

// Исключение
class BatchExceptionYouCannotCallOtherFunctionsFromThePutFunction extends BatchException
{
    // Конструктор
    public function __construct(protected BatchPromiseContext $promiseContext)
    {
        parent::__construct($promiseContext->managerContext, 'Вы не можете вызывать другие функции из функции PUT');
    }
}
