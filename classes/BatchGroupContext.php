<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpLog;
use Shasoft\CDump\CDumpHtml;
use Shasoft\Batch\BatchGroupConfig;
use Psr\Cache\CacheItemPoolInterface;


// Контекст группы обещаний менеджера группировки
class BatchGroupContext
{
    // Группа 
    public BatchGroup $group;
    // Список контекстов обещаний (вызовов группы)
    public array $promisesContext = [];
    // Приоритет
    public int $priority;
    // КЭШирование GET
    public ?CacheItemPoolInterface $cacheGet;
    // КЭШирование PUT
    public ?CacheItemPoolInterface $cachePut;
    // Тип КЭШ-а
    public const CACHE_NONE     = 1; // Функция без КЭШирования
    public const CACHE_GET      = 2; // Функция вида Get (кэширование до изменения значения функцией вида Put)
    public const CACHE_PUT      = 4; // Функция вида Put (функция для изменения значения)
    public const CACHE_LIFETIME = 8; // Функция вида LifeTime (кэширование на время)
    // Дополнительные расчетные значения
    const CACHE_READ = self::CACHE_GET | self::CACHE_LIFETIME;
    public int $cacheType = self::CACHE_NONE;
    // Время жизни КЭШа типа LIFETIME
    public int $cacheTtl;
    // Список ключей аргументов для PUT
    public array $indexKeys;
    // Конструктор
    public function __construct(public BatchManagerContext $managerContext, public string $key, public \Closure $fnExecuter, ?callable $fnConfig)
    {
        // Создать группу
        $this->group = new BatchGroup($this);
        // Параметры группы по умолчанию
        $groupConfig = new BatchGroupConfig($this);
        // Приоритет
        $groupConfig->setPriority(BatchManager::PRIORITY_NORMAL);
        // Установить интерфейсы КЭШа (по умолчанию берем из глобальных настроек)
        $groupConfig->setICache(BatchConfig::getICacheGet(), BatchConfig::getICachePut());
        // Вызвать пользовательский обработчик
        if (is_callable($fnConfig)) {
            $fnConfig($groupConfig);
        }
        CDumpLog::dump(BatchDebug::hasLog(), 'create group &1', $this);
    }
    // Получить объект как строку
    public function __toString(): string
    {
        $object_id = CDumpHtml::object_id($this);
        return __CLASS__ . '( ' . $this->key . ' )' . $object_id;
    }
    // Выполнить группу обещаний
    public function execute(): void
    {
        CDumpLog::group(BatchDebug::hasLog(), function (): void {
            // Добавить в лог
            $this->managerContext->addToLog($this);
            // Попробовать выполнить обещания через КЭШ
            $promisesContext = $this->promisesContext;
            $promisesContextCacheExecuted = [];
            $this->promisesContext = [];
            foreach ($promisesContext as $promiseContext) {
                // Если обещание выполнилось
                if ($promiseContext->hasExecute() || BatchCache::execute($promiseContext)) {
                    $promisesContextCacheExecuted[] = $promiseContext;
                } else {
                    $this->promisesContext[] = $promiseContext;
                }
            }
            // Выполнить пакет группировки (если группа содержит обещания)
            if (!empty($this->promisesContext)) {
                CDumpLog::group(BatchDebug::hasLog(), function () {
                    $this->managerContext->groupContext = $this;
                    $fnExecuter = $this->fnExecuter;
                    $fnExecuter($this->group);
                    $this->managerContext->groupContext = null;
                }, 'setGroupContent &1', $this);
            }
            // Добавить выполненные
            $this->promisesContext = array_merge($this->promisesContext, $promisesContextCacheExecuted);
        });
    }
}
