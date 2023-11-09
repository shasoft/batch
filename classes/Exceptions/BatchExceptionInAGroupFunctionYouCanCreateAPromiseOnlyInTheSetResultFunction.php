<?php

namespace Shasoft\Batch\Exceptions;

use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchException;
use Shasoft\Batch\BatchManagerContext;

// Исключение
class BatchExceptionInAGroupFunctionYouCanCreateAPromiseOnlyInTheSetResultFunction extends BatchException
{
    // Конструктор
    public function __construct(BatchManagerContext $context)
    {
        parent::__construct($context, 'В групповой функции можно создавать обещание только в функции setResult');
    }
}
