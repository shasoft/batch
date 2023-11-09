<?php

namespace Shasoft\Batch;

// Результат работы функции вида PUT
class BatchCachePutDummy
{
    // Конструктор
    public function __construct(public string $hashKey, public string $hashValue)
    {
    }
    // Получить объект как строку
    public function __toString(): string
    {
        return
            '<strong style="color:' .
            BatchDebug::$colors[BatchGroupContext::CACHE_PUT] . '">' .
            $this->hashKey . '</strong> = <span style="color:' .
            BatchDebug::$colors[BatchGroupContext::CACHE_GET] . '">' .
            $this->hashValue .
            '</span>';
    }
}
