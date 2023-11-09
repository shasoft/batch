<?php

namespace Shasoft\Batch;

// Исключение
class BatchException extends \Exception
{
    // Конструктор
    public function __construct(protected ?BatchManagerContext $managerContext, string $message)
    {
        parent::__construct($message, 666);
    }
    // Получить контекст менеджера
    public function  managerContext(): BatchManagerContext
    {
        return $this->managerContext;
    }
}
