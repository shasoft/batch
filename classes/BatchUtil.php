<?php

namespace Shasoft\Batch;

// Вспомогательные функции
class BatchUtil
{
    // Получить контекст
    static public function getContext(BatchPromise|BatchGroup|null $object = null): BatchPromiseContext|BatchGroupContext|BatchManagerContext|null
    {
        $refObject = new \ReflectionClass(is_null($object) ? BatchManager::class : $object);
        $refContext = $refObject->getProperty('context');
        $refContext->setAccessible(true);
        return $refContext->getValue($object);
    }
    // Обнулить текущий контекст
    static public function clearManagerContext(): void
    {
        $refObject = new \ReflectionClass(BatchManager::class);
        $refContext = $refObject->getProperty('context');
        $refContext->setAccessible(true);
        $refContext->setValue(null, null);
    }
    // Это обещание
    static public function hasPromise(mixed $value): bool
    {
        return is_object($value) && $value instanceof BatchPromise;
    }
}
