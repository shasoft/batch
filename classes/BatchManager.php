<?php

namespace Shasoft\Batch;

use Shasoft\Batch\BatchDebug;
use Shasoft\CDump\CDumpLog;
use Shasoft\Batch\BatchConfig;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchGroupContext;
use Shasoft\Batch\BatchManagerContext;
use Shasoft\Batch\BatchPromiseContext;
use Shasoft\Batch\Exceptions\BatchExceptionTheRunMethodTakesAnArrayOfPromisesAsAParameter;
use Shasoft\Batch\Exceptions\BatchExceptionInAGroupFunctionYouCanCreateAPromiseOnlyInTheSetResultFunction;


// Менеджер группировок вызовов
class BatchManager
{
    // Приоритеты
    const PRIORITY_LOW = -1000;
    const PRIORITY_NORMAL = 0;
    const PRIORITY_HIGH = 1000;
    // Контекст менеджера
    static protected ?BatchManagerContext $context = null;
    // Создать задачу группировки (расширенный вариант)
    static public function createEx(?callable $fnConfig, callable $fnExecuter, ...$args): BatchPromise
    {
        return CDumpLog::group(BatchDebug::hasLog(), function () use ($fnConfig, $fnExecuter, $args) {
            // В групповой функции можно создавать обещание только в функции setResult
            if (!is_null(self::$context->groupContext) && is_null(self::$context->promiseContext)) {
                throw new BatchExceptionInAGroupFunctionYouCanCreateAPromiseOnlyInTheSetResultFunction(self::$context);
            }
            // Сгенерировать ключ пакетной группировки
            $keyBatch = BatchConfig::getBatchKey($fnExecuter);
            // Проверим наличие группы по имени
            if (!array_key_exists($keyBatch, self::$context->groupsContext)) {
                // Если нет то создадим группу
                $groupContext = new BatchGroupContext(self::$context, $keyBatch, $fnExecuter, $fnConfig);
                // Добавим группу
                self::$context->groupsContext[$keyBatch] = $groupContext;
            }
            // Получим группу
            $groupContext = self::$context->groupsContext[$keyBatch];
            // Создадим обещание
            $promiseContext = new BatchPromiseContext(self::$context, $groupContext, $args);
            // Событие создания
            BatchCache::create($promiseContext);
            // Добавим вызов в группу
            $groupContext->promisesContext[] = $promiseContext;
            // Логирование
            CDumpLog::dump(BatchDebug::hasLog(), 'create promise &1 parent &2', $promiseContext, $promiseContext->parent);
            // Вернуть созданную задачу
            return $promiseContext->promise;
        });
    }
    // Создать задачу группировки
    static public function create(callable $fnExecuter, ...$args): BatchPromise
    {
        return self::createEx(null, $fnExecuter, ...$args);
    }
    // Создать задачу ожидания других задач
    static public function all(array $promises): BatchPromise
    {
        return CDumpLog::group(BatchDebug::hasLog(), function () use ($promises) {
            // Создать обещание
            $promiseContext = new BatchPromiseContext(self::$context, null, array_map(function ($promise) {
                if (!BatchUtil::hasPromise($promise)) {
                    throw new BatchExceptionTheRunMethodTakesAnArrayOfPromisesAsAParameter(self::$context);
                }
                return BatchUtil::getContext($promise);
            }, $promises));
            // Если передан пустой массив обещаний?
            if (empty($promises)) {
                // то значит обещание сразу выполнилось
                $promiseContext->setResult('all', []);
            } else {
                // Во все переданные обещания добавить что результат нужно записать в созданное обещание
                foreach ($promiseContext->args as $key => $argPromiseContext) {
                    // Добавить ссылку что нужно записать результат
                    $argPromiseContext->resultTo[] = [$key, $promiseContext];
                    // Если обещание закончено
                    if ($argPromiseContext->hasFinish()) {
                        // то сразу записать результат
                        $promiseContext->setResultAll($key, $argPromiseContext->result());
                    }
                }
            }
            //s_dd($promiseContext, $promiseContext->hasExecute(), $promiseContext->hasFinish());
            CDumpLog::dump(BatchDebug::hasLog(), 'create promise &1 parent &2', $promiseContext, $promiseContext->parent);
            // Вернуть указатель на созданное обещание
            return $promiseContext->promise;
        });
    }
    // Выполнить группировку
    static public function run(callable $fnExecuter): mixed
    {
        try {
            // Если контекст не создан
            if (is_null(self::$context)) {
                // Создать контекст
                self::$context = new BatchManagerContext(BatchDebug::$debug);
            }
            // Запустить метод выполнения и вернуть полученные из асинхронного режима данные
            return self::$context->run($fnExecuter);
        } catch (\Throwable $th) {
            // Обнулить контекст
            self::$context = null;
            // Бросить дальше исключение
            throw $th;
        }
    }
}
