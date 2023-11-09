<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpLog;
use Shasoft\Batch\BatchDebug;
use Shasoft\PsrCache\CacheItem;
use Shasoft\Batch\Exceptions\BatchExceptionYouCannotCallOtherFunctionsFromThePutFunction;

// Работа с КЭШем
class BatchCache
{
    // Ключи для сохранения в КЭШ
    protected const KEY_VALUE = 'value';
    protected const KEY_PUTS = 'puts';
    protected const KEY_EXPIRE = 'expire';
    // Ключ
    public string $hashKey;
    // Значение данных (для PUT)
    public string $hashValue;
    // Ключи PUT для GET
    public array $puts = [];
    // Префикс
    static protected string $key_put_prefix = '';
    // Конструктор
    public function __construct()
    {
    }
    // Событие установки результата
    static public function setResult(BatchPromiseContext $promiseContext): void
    {
        // Если нужно работать с КЭШем (если значение прочитано из КЭШа, то это свойство = null)
        //s_dump($promiseContext->groupContext->key, $promiseContext->cacheMode, $promiseContext->groupContext->cacheType);
        if (in_array($promiseContext->cacheMode, [BatchPromise::MODE_CACHE_ON, BatchPromise::MODE_CACHE_DIRECT], true)) {
            //s_dump(is_null($promiseContext->cache));
            if (!is_null($promiseContext->cache)) {
                $groupContext = $promiseContext->groupContext;
                $cache = $promiseContext->cache;
                // Создать элемент КЭШ-а
                $itemCache = new CacheItem($cache->hashKey);
                switch ($groupContext->cacheType) {
                    case BatchGroupContext::CACHE_LIFETIME: {
                            $itemCache->set([
                                // Значение
                                self::KEY_VALUE => $promiseContext->result['group'],
                                // Время, до которого живет значение
                                self::KEY_EXPIRE => time() + $groupContext->cacheTtl
                            ]);
                            /*
                        $groupContext->cacheGet->put($cache->hashKey, [
                            // Значение
                            self::KEY_VALUE => $promiseContext->result['group'],
                            // Время, до которого живет значение
                            self::KEY_EXPIRE => time() + $groupContext->cacheTtl
                        ]);
                        //*/
                        }
                        break;
                    case BatchGroupContext::CACHE_GET: {
                            $itemCache->set([
                                // Значение
                                self::KEY_VALUE => $promiseContext->result['group'],
                                // Зависимые ключи PUT которые служат для сброса значения
                                self::KEY_PUTS => $cache->puts
                            ]);
                            /*
                        $groupContext->cacheGet->put($cache->hashKey, [
                            // Значение
                            self::KEY_VALUE => $promiseContext->result['group'],
                            // Зависимые ключи PUT которые служат для сброса значения
                            self::KEY_PUTS => $cache->puts
                        ]);
                        //*/
                        }
                        break;
                    case BatchGroupContext::CACHE_PUT: {
                            $itemCache->set($cache->hashValue);
                            /*
                        $groupContext->cachePut->put($cache->hashKey, $cache->hashValue);
                        //*/
                        }
                        break;
                    default: {
                            throw new \Exception('Error type CACHE ' . $groupContext->cacheType);
                        }
                }
                // Сохранить элемент КЭШ-а в КЭШе
                $groupContext->cacheGet->save($itemCache);
            }
        }
    }
    // Установить зависимость
    static public function setPuts(?BatchPromiseContext $promiseContext, string $key, string $value): void
    {
        CDumpLog::group(BatchDebug::hasLog(), function () use ($promiseContext, $key, $value): void {
            if (!is_null($promiseContext)) {
                if (!is_null($promiseContext->groupContext->cacheGet)) {
                    // Если это обещание вида GET
                    if ($promiseContext->groupContext->cacheType == BatchGroupContext::CACHE_GET) {
                        // то добавить в него ключ и значение
                        if (!is_null($promiseContext->cache)) {
                            $promiseContext->cache->puts[$key] = $value;
                        }
                    }
                    // Установить для родителя
                    self::setPuts($promiseContext->parent, $key, $value);
                }
            }
        });
    }
    // Событие создания обещания
    static public function create(BatchPromiseContext $promiseContext): void
    {
        CDumpLog::group(BatchDebug::hasLog(), function () use ($promiseContext): void {
            // Из функции вида PUT нельзя вызывать никакие функции!
            if (($promiseContext->cacheTypeParent & BatchGroupContext::CACHE_PUT) != 0) {
                throw new BatchExceptionYouCannotCallOtherFunctionsFromThePutFunction($promiseContext);
            }
            if ($promiseContext->groupContext->cacheType == BatchGroupContext::CACHE_PUT) {
                // Получить список аргументов ключа и значения
                $keyGet = [];
                $valuePut = [];
                foreach ($promiseContext->args as $index => $arg) {
                    if (array_key_exists($index, $promiseContext->groupContext->indexKeys)) {
                        $keyGet[] =  $arg;
                    } else {
                        $valuePut[] = $arg;
                    }
                }
                // Сгенерировать ключ
                $hashKey = self::$key_put_prefix . BatchConfig::getArgsKey(array_merge([$promiseContext->groupContext->key], $keyGet));
                // Сгенерировать ХЕШ значения
                $hashValue = BatchConfig::getArgsKey($valuePut);
                //s_dd($valuePut, $hashValue);
                //s_dump($hashKey, $keyGet, $hashValue, $valuePut, ($promiseContext->cacheTypeParent & BatchGroupContext::CACHE_READ));
                // Если это вызов из GET | LIFETIME
                if (($promiseContext->cacheTypeParent & BatchGroupContext::CACHE_READ) == 0) {
                    // 1. Функция PUT изменение данных
                    $promiseContext->cache = new self;
                    $promiseContext->cache->hashKey = $hashKey;
                    $promiseContext->cache->hashValue = $hashValue;
                } else {
                    //s_dump($promiseContext->cacheTypeParent, ($promiseContext->cacheTypeParent & BatchGroupContext::CACHE_PUT));
                    //s_dump($key, $value);
                    if (($promiseContext->cacheTypeParent & BatchGroupContext::CACHE_PUT) == 0) {
                        // 2. Функция PUT указания события изменения КЭШа потому что вызывается из GET
                        // Добавить ключи во все родительские обещания вида GET
                        self::setPuts($promiseContext->parent, $hashKey, $hashValue);
                        // Если КЕШирование не выключено
                        if ($promiseContext->cacheMode != BatchPromise::MODE_CACHE_OFF) {
                            // Записать в КЭШ
                            if (!is_null($promiseContext->groupContext->cachePut)) {
                                $itemCache = new CacheItem($hashKey);
                                $itemCache->set($hashValue);
                                $promiseContext->groupContext->cachePut->save($itemCache);
                                /*
                            $promiseContext->groupContext->cachePut->put($hashKey, $hashValue);
                            //*/
                            }
                        }
                    } else {
                        // 3. Функция PUT вызывается из GET и PUT
                        // Просто пропускается потому что ничего не нужно делать
                    }
                    // Записать в результат ключ и значение
                    $result = new BatchCachePutDummy($hashKey, $hashValue);
                    $promiseContext->result = [
                        'group' => $result,
                        'finish' => $result
                    ];
                }
            }
        });
    }
    // Событие выполнения обещания
    static public function execute(BatchPromiseContext $promiseContext): bool
    {
        return CDumpLog::group(BatchDebug::hasLog(), function () use ($promiseContext): bool {
            $hasReadFromCache = false;
            // Если КЭШ включен

            // Если указан КЭШ
            if (!is_null($promiseContext->groupContext->cacheGet)) {
                // Обработка в зависимости от типа
                switch ($promiseContext->groupContext->cacheType) {
                    case BatchGroupContext::CACHE_LIFETIME:
                    case BatchGroupContext::CACHE_GET: {
                            //
                            if (($promiseContext->cacheTypeParent & BatchGroupContext::CACHE_PUT) == 0) {
                                // Читать значение из КЭШа?
                                $hasReadFromCache = false;
                                // 1. GET|LIFETIME Это вызов НЕ из PUT
                                // Ключ
                                $hashKey = BatchConfig::getArgsKey(array_merge([$promiseContext->groupContext->key], $promiseContext->args));
                                // Проверять только если КЭШ включен
                                if ($promiseContext->cacheMode == BatchPromise::MODE_CACHE_ON) {
                                    //
                                    $itemCache = $promiseContext->groupContext->cacheGet->getItem($hashKey);
                                    // Проверить значение в КЭШ-е
                                    if ($itemCache->isHit()) {
                                        // Получить значение
                                        $item = $itemCache->get();
                                        // В зависимости от типа
                                        if ($promiseContext->groupContext->cacheType == BatchGroupContext::CACHE_LIFETIME) {
                                            // BatchGroupContext::CACHE_LIFETIME
                                            // Получить текущее время
                                            $now = time();
                                            // Текущее время меньше чем время жизни значения в КЕШ-е
                                            if ($now < $item[self::KEY_EXPIRE]) {
                                                // Данные прочитаны из КЕШа
                                                $hasReadFromCache = true;
                                            } else {
                                                // Увеличить время на 30 секунд чтобы пока в этом сеансе будет идти расчет нового значения, 
                                                // то остальные сеансы использовали бы старое значение
                                                $item[self::KEY_EXPIRE] += 30;
                                                // Установить новое значение в КЕШ
                                                $itemCache->set($item);
                                                $promiseContext->groupContext->cacheGet->save($itemCache);
                                                /*
                                            $promiseContext->groupContext->cacheGet->put($hashKey, $item);
                                            //*/
                                            }
                                        } else if ($promiseContext->groupContext->cacheType == BatchGroupContext::CACHE_GET) {
                                            // BatchGroupContext::CACHE_GET
                                            // По умолчанию считаем что значение из КЭШа прочитано
                                            $hasReadFromCache = true;
                                            // Проверить все дочерние ключи на изменение
                                            foreach ($item[self::KEY_PUTS] as $_hashKey => $_hashValue) {
                                                /*
                                            // Есть такой ключ?
                                            if ($_itemCache->isHit()) {
                                                // Читать значение ключа
                                                $value_save = $promiseContext->groupContext->cachePut->get($_hashKey);
                                            } else {
                                                // Значения ключа нет
                                                $value_save = null;
                                            }
                                            //*/
                                                // Ключи отличаются?
                                                if ($_hashValue != $promiseContext->groupContext->cachePut->getItem($_hashKey)->get()) {
                                                    // Данные изменились, читать из КЭШа не нужно
                                                    $hasReadFromCache = false;
                                                    // дальше можно не продолжать проверять
                                                    break;
                                                }
                                            }
                                        }
                                        // Если данные не изменились
                                        if ($hasReadFromCache) {
                                            //s_dump($promiseContext->groupContext->key, $hasReadFromCache, BatchDebug::hasLog(), $item);
                                            // Добавить ключи во все родительские обещания вида GET
                                            if (array_key_exists(self::KEY_PUTS, $item)) {
                                                foreach ($item[self::KEY_PUTS] as $_hashKey => $_hashValue) {
                                                    self::setPuts($promiseContext->parent, $_hashKey, $_hashValue);
                                                }
                                            }
                                            // Установить значение из КЭШа
                                            $promiseContext->setResult('group', $item[self::KEY_VALUE]);
                                        }
                                    }
                                }
                                // Если данные нужно читать напрямую
                                if ($hasReadFromCache === false) {
                                    // то создать объект КЭШ-а чтобы затем сохранить полученное значение в КЭШ
                                    $promiseContext->cache = new self;
                                    $promiseContext->cache->hashKey = $hashKey;
                                    // Если это тип GET, то создать массив для сохранения ключей PUT
                                    if ($promiseContext->groupContext->cacheType == BatchGroupContext::CACHE_GET) {
                                        $promiseContext->cache->puts = [];
                                    }
                                }
                            } else {
                                // 2. GET|LIFETIME Это вызов из PUT
                                // Вызов без КЭШирования
                                // Ничего не делаем
                            }
                        }
                        break;
                }
            }
            return $hasReadFromCache;
        });
    }
}
