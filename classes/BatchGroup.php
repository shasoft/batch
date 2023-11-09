<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpLog;
use Shasoft\Batch\BatchGroupResults;
use Shasoft\Batch\Exceptions\BatchExceptionPromiseKept;

// Пакет сгруппированных задач
class BatchGroup
{
    // Конструктор
    public function __construct(protected BatchGroupContext $context)
    {
    }
    // Установить результат для всех набором аргументов
    public function setResult(BatchGroupResults|callable|null $cb): void
    {
        CDumpLog::group(BatchDebug::hasLog(), function () use ($cb): void {
            $managerContext = $this->context->managerContext;
            foreach ($this->context->promisesContext as $promiseContext) {
                // Если обещание уже выполнено
                if ($promiseContext->hasExecute()) {
                    throw new BatchExceptionPromiseKept($promiseContext);
                }
                if (is_null($cb)) {
                    $result = null;
                } else if ($cb instanceof BatchGroupResults) {
                    // Получить значение из объекта значений
                    $result = $cb->get(...$promiseContext->args);
                } else {
                    // Установить контекст обещания как текущий выполняемый
                    $managerContext->promiseContext = $promiseContext;
                    // Получить результат
                    $result = call_user_func_array($cb, $promiseContext->args);
                    // Убрать текущий выполняемый контекст обещания
                    $managerContext->promiseContext = null;
                }
                // Записать результат выполнения группы
                $promiseContext->setResult('group', $result);
            }
        });
    }
    // Аргументы
    public function args(): BatchGroupArgs
    {
        $args = [];
        foreach ($this->context->promisesContext as $promiseContext) {
            $args[] = $promiseContext->args;
        }
        return new BatchGroupArgs($args);
    }
}
