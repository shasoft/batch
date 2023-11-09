<?php

namespace Shasoft\Batch;

use Shasoft\Batch\BatchGroupContext;
use Psr\Cache\CacheItemPoolInterface;


// Параметры группы
class BatchGroupConfig
{
    // Конструктор
    public function __construct(protected BatchGroupContext $context)
    {
    }
    // Установить приоритет
    public function setPriority(int $priority): static
    {
        $this->context->priority = $priority;
        // Вернуть указатель на себя
        return $this;
    }
    // Установить интерфейсы КЭШа 
    public function setICache(?CacheItemPoolInterface $cacheGet, ?CacheItemPoolInterface $cachePut): static
    {
        $this->context->cacheGet = $cacheGet;
        $this->context->cachePut = $cachePut;
        // Вернуть указатель на себя
        return $this;
    }
    // Тип КЭШ-а
    public function setCacheNone(): static
    {
        $this->context->cacheType = BatchGroupContext::CACHE_NONE;
        // Вернуть указатель на себя
        return $this;
    }
    // Тип КЭШ-а
    public function setCacheGet(): static
    {
        $this->context->cacheType = BatchGroupContext::CACHE_GET;
        // Вернуть указатель на себя
        return $this;
    }
    // Тип КЭШ-а
    public function setCachePut(...$indexKeys): static
    {
        $this->context->cacheType = BatchGroupContext::CACHE_PUT;
        // Номера ключевых аргументов
        $this->context->indexKeys = [];
        foreach ($indexKeys as $index) {
            $this->context->indexKeys[$index] = 1;
        }
        // Вернуть указатель на себя
        return $this;
    }
    // Тип КЭШ-а
    public function setCacheLifetime(int $ttl): static
    {
        $this->context->cacheType = BatchGroupContext::CACHE_LIFETIME;
        $this->context->cacheTtl = $ttl;
        // Вернуть указатель на себя
        return $this;
    }
}
