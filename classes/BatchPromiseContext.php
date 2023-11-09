<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpLog;
use Shasoft\CDump\CDumpHtml;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\Exceptions\BatchExceptionTheResultOfTheAllFunctionMustBeAnArray;


// Контекст задачи менеджера группировки
class BatchPromiseContext
{
    // Нумератор
    static protected int $gen_id = 0;
    // Идентификатор
    public int $id;
    // Обещание
    public BatchPromise $promise;
    // Список куда записывать итоговое значения этого обещания
    public array $resultTo = [];
    // Параметры группировки
    public array $callbacks = [];
    // Результат работы
    // all - резулльтат выполнения обещания вида all
    // group - результат выполнения группы BatchGroup
    // then - результат выполнения функций then
    // finish - итоговый результат
    public array $result = [];
    // Вышестоящее обещание
    public ?BatchPromiseContext $parent;
    // OR всех вышестоящих cacheType
    public int $cacheTypeParent = 0;
    // Объект КЭШирования
    public ?BatchCache $cache = null;
    // Режим работы с КЭШем
    public int $cacheMode = BatchPromise::MODE_CACHE_ON;
    // Конструктор
    public function __construct(public BatchManagerContext $managerContext, public ?BatchGroupContext $groupContext, public array $args)
    {
        // Идентификатор
        $this->id = (++self::$gen_id);
        // Создать обещание
        $this->promise = new BatchPromise($this);
        // Родительское обещание
        $this->parent = $managerContext->promiseContext;
        // Если есть родительское обещание
        if (!is_null($this->parent)) {
            // Родительский тип
            if (!is_null($this->parent->groupContext)) {
                $this->cacheTypeParent |= $this->parent->groupContext->cacheType;
            }
            // Режим КЭШирования
            $this->cacheMode = $this->parent->cacheMode;
        }
        // Если это обещание вида All
        if (is_null($groupContext)) {
            $this->result['all'] = [];
        }
        // Добавить в лог
        $this->managerContext->addToLog($this);
    }
    // Получить объект как строку
    public function __toString(): string
    {
        $object_id = CDumpHtml::object_id($this->id);
        if ($this->hasAll()) {
            return '@all' . $object_id . '(' . implode(',', array_map(function (BatchPromiseContext $promiseContext) {
                return CDumpHtml::object_id($promiseContext->id);
            }, $this->args)) . ')';
        }
        //$args = '<span style="color:Brown">' . substr(CDumpHtml::toString($object->args), 1, -1) . '</span>';
        return get_class($this) . $object_id;
    }
    // Это обещание вида ALL?
    public function hasAll(): bool
    {
        return is_null($this->groupContext);
    }
    // Обещание выполнено?
    public function hasExecute(): bool
    {
        return array_key_exists('group', $this->result);
    }
    // Обещание закончено?
    public function hasFinish(): bool
    {
        return array_key_exists('finish', $this->result);
    }
    // Результат (итоговый)
    public function result(string $name = 'finish'): mixed
    {
        // Это обычное обещание
        $ret = array_key_exists($name, $this->result) ? $this->result[$name] : new BatchValueUndefined;
        return $ret;
    }
    // Записать результат в обещание вида all и проверить его на окончание
    public function setResultAll(mixed $key, mixed $result): void
    {
        CDumpLog::group(BatchDebug::hasLog(), function () use ($key, $result): void {
            // Добавить выполненное обещание
            $all = $this->result['all'];
            $all[$key] = $result;
            // Изменить куда записывать
            $this->setResult('all', $all);
        });
    }
    // Записать результат в обещание и проверить его на окончание
    public function setResult(mixed $key, mixed $result): void
    {
        CDumpLog::group(BatchDebug::hasLog(), function () use ($key, $result): void {
            // А может результатом является обещание?
            while (BatchUtil::hasPromise($result)) {
                // Контекст обещания
                $resultContext = BatchUtil::getContext($result);
                // А может обещание уже выполнено?
                if ($resultContext->hasFinish()) {
                    $result = $resultContext->result();
                } else {
                    // Записать в контекст результирующего обещания что его результат нужно записать в текущее обещание
                    $resultContext->resultTo[] = [$key, $this];
                    break;
                }
            }
            if (!BatchUtil::hasPromise($result)) {
                // Записать значение
                $this->result[$key] = $result;
                // Если это значение обещания вида all
                if ($key == 'all') {
                    // Если все обещания выполнены
                    if (count($result) == count($this->args)) {
                        $this->setResult('group', $result);
                    }
                } else {
                    // Если это значение группы
                    if ($key == 'group') {
                        // то записать его и в значение then
                        $this->result['then'] = $result;
                        // и вызвать функции работы с КЭШем
                        BatchCache::setResult($this);
                    }
                    // Если есть обработчики
                    if (!empty($this->callbacks)) {
                        // Вытащить очередной обработчик
                        $callback = array_shift($this->callbacks);
                        if (is_callable($callback)) {
                            // Установить контекст обещания как текущий выполняемый
                            $this->managerContext->promiseContext = $this;
                            // Получить результат
                            //$result = $this->hasAll() ? $this->result : $this->result['then'];
                            //s_dump($this->result);
                            $result = call_user_func($callback, $this->result['then']);
                            // Убрать текущий выполняемый контекст обещания
                            $this->managerContext->promiseContext = null;
                            // Записать результат
                            $this->setResult('then', $result);
                        }
                    }
                    // А может обещание закончено но результат ещё не установлен?
                    if (empty($this->callbacks) && !array_key_exists('finish', $this->result) && !BatchUtil::hasPromise($result)) {
                        // Установить результат (если это не обещание вида ALL)
                        $this->result['finish'] = $result;
                        // Текущий результат
                        //$result = $this->result();
                        // Записать результат во все зависимые обещания
                        foreach ($this->resultTo as $item) {
                            //
                            $key = $item[0];
                            $promiseContextTo = $item[1];
                            // Нужно записать результат в обещание вида all?
                            if ($promiseContextTo->hasAll()) {
                                $promiseContextTo->setResultAll($key, $result);
                            } else {
                                $promiseContextTo->setResult($key, $result);
                            }
                        }
                    }
                }
            }
        });
    }
}
