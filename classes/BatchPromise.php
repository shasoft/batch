<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpHtml;
use Shasoft\Batch\Exceptions\BatchExceptionIncorrectCacheMode;


// Задача менеджера группировки
class BatchPromise
{
    // Тип работы с КЭШем
    public const MODE_CACHE_OFF      = 1; // Отключить КЭШирование
    public const MODE_CACHE_DIRECT   = 2; // Отключить КЭШирование, но сохранять изменения в КЭШ    
    public const MODE_CACHE_ON       = 4; // Включить КЭШирование (значение по умолчанию)
    // Конструктор
    public function __construct(protected BatchPromiseContext $context)
    {
    }
    // Получить объект как строку
    public function __toString(): string
    {
        return CDumpHtml::toString($this->context);
    }
    // Функция для указания функции возврата результата
    public function then(callable $callback): static
    {
        $this->context->callbacks[] = $callback;
        // Вернуть указатель на себя
        return $this;
    }
    // Установить режим работы с КЭШем
    public function setCacheMode(int $mode): static
    {
        if (!in_array($mode, [
            self::MODE_CACHE_OFF,
            self::MODE_CACHE_DIRECT,
            self::MODE_CACHE_ON
        ], true)) {
            throw new BatchExceptionIncorrectCacheMode($mode);
        }
        // Установить режим обещания
        $this->context->cacheMode = $mode;
        // Если это обещание вида All
        if ($this->context->hasAll()) {
            // то проставить режим для всех дочерних обещаний
            foreach ($this->context->args as $promiseContext) {
                $promiseContext->promise->setCacheMode($mode);
            }
        }
        // Вернуть указатель на себя
        return $this;
    }
}
