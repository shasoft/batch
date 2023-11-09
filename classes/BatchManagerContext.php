<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpLog;
use Shasoft\Batch\Exceptions\BatchExceptionRunMethodMustReturnAPromise;
use Shasoft\Batch\Exceptions\BatchExceptionTheResultingPromiseIsNotFinished;
use Shasoft\Batch\Exceptions\BatchExceptionTheResultingPromiseIsNotFulfilled;

// Контекст менеджера группировок вызовов
class BatchManagerContext
{
    // Список групп
    public array $groupsContext = [];
    // Текущая выполняемая группа
    public ?BatchGroupContext $groupContext = null;
    // Текущее выполняемое обещание
    public ?BatchPromiseContext $promiseContext = null;
    // Конструктор
    public function __construct(public ?BatchDebug $debug)
    {
        if (!is_null($debug)) {
            $debug->managerContext = $this;
        }
    }
    // Выполнить группировку
    public function run(callable $fnExecuter): mixed
    {
        return CDumpLog::group(BatchDebug::hasLog(), function () use ($fnExecuter) {
            // Запустить функцию генерации пакетных задач и вернуть итоговое обещание
            $promiseRet = $fnExecuter();
            if (!($promiseRet instanceof BatchPromise)) {
                throw new BatchExceptionRunMethodMustReturnAPromise($this);
            }
            // Event loop
            while (!empty($this->groupsContext)) {
                // Искать группу с наибольшим приоритетом
                $groupContextHight = $this->groupsContext[array_key_first($this->groupsContext)];
                foreach ($this->groupsContext as $key => $groupContext) {
                    if ($groupContextHight !== $groupContext) {
                        if ($groupContext->priority > $groupContextHight->priority) {
                            $groupContextHight = $groupContext;
                        }
                    }
                }
                // Взять контекст группы (удалив его из обработки)
                unset($this->groupsContext[$groupContextHight->key]);
                // Выполнить группу обещаний
                $groupContextHight->execute();
            }
            // Получить контекст возвращаемого обещания
            $promiseRetContext = BatchUtil::getContext($promiseRet);
            // Проверим что результирующее обещание выполнено
            if (!$promiseRetContext->hasExecute()) {
                throw new BatchExceptionTheResultingPromiseIsNotFulfilled($promiseRetContext);
            }
            // Проверим что результирующее обещание закончено
            if (!$promiseRetContext->hasFinish()) {
                throw new BatchExceptionTheResultingPromiseIsNotFinished($promiseRetContext);
            }
            // Вернуть полученные из асинхронного режима данные
            return $promiseRetContext->result();
        });
    }
    // Добавить в лог
    public function addToLog(mixed $object)
    {
        if (!is_null($this->debug)) {
            $this->debug->add($object);
        }
    }
}
