<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchUtil;
use Shasoft\CDump\CDumpHtml;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\IsEqual;

class Base extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Обнулить контекст чтобы каждый тест начинался "с нуля"
        BatchUtil::clearManagerContext();
    }
    public function tearDown(): void
    {
        parent::tearDown();
    }
    protected function onNotSuccessfulTest(\Throwable $t): never
    {
        /*
        echo "================\n";
        foreach (BatchDebug::getTexts() as $text) {
            echo $text . "\n";
        }
        //*/
        throw $t;
    }
    final public static function assertValue(mixed $value, string $excepted): void
    {
        $strValue = CDumpHtml::toString($value);
        $constraint = new IsEqual($strValue);
        self::assertThat($excepted, $constraint, "Значение не соответствует заявленному");
    }
}
