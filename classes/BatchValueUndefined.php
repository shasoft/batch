<?php

namespace Shasoft\Batch;

// Неопределенное значение
class BatchValueUndefined
{
    // Получить объект как строку
    public function __toString(): string
    {
        return
            '<strong style="color:LightCoral">undefined</strong>';
    }
}
