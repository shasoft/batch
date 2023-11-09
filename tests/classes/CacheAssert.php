<?php

namespace Shasoft\Batch\Tests;

use Shasoft\Batch\BatchUtil;
use Shasoft\Batch\BatchDebug;
use Shasoft\Batch\BatchConfig;
use PHPUnit\Framework\TestCase;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\ApiTest\BatchTestCacheFns;


class CacheAssert
{
    public BatchTestCacheFns $fns;
    public CacheMem $cache;
    public BatchDebug $debug;
    public mixed $ret;
    // Конструктор
    public function __construct(protected string $key, protected mixed $item)
    {
    }
    // Проверить наличие ссылок
    protected function asString(mixed $value): string
    {
        if (is_array($value)) {
            $value = $this->arrMap($value);
        }
        return json_encode($value);
    }
    // Проверить наличие ссылок
    protected function arrMap(?array $values): array
    {
        $ret = [];
        if ($values) {
            $keys = array_keys($values);
            sort($keys);
            foreach ($keys as $key) {
                //
                $val = $values[$key];
                //
                $ret[$key] = is_array($val) ? $this->arrMap($val) : $val;
            }
        }
        return $ret;
    }
    // Проверить наличие значения
    public function value(mixed $value): self
    {
        TestCase::assertEquals($this->asString($this->item['value']), $this->asString($value), 'Значение не совпадает со значением в КЭШе');
        // Вернуть указатель на себя
        return $this;
    }
    // Проверить наличие значения
    public function val(mixed $value): self
    {
        TestCase::assertEquals($this->asString($this->item), $this->asString($value), 'Значение не совпадает со значением в КЭШе');
        // Вернуть указатель на себя
        return $this;
    }
    // Проверить наличие ссылок
    public function puts(array $puts): self
    {
        TestCase::assertEquals($this->asString($this->item['puts']), $this->asString($puts), 'Значение не совпадает со значением в КЭШе');
        // Вернуть указатель на себя
        return $this;
    }
}
