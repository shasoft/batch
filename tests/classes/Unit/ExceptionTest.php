<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\ApiTest\BatchTestFns;
use Shasoft\Batch\BatchException;
use Shasoft\Batch\Exceptions\BatchExceptionPromiseAlreadyFulfilled;
use Shasoft\Batch\Exceptions\BatchExceptionRunMethodMustReturnAPromise;
use Shasoft\Batch\Exceptions\BatchExceptionTheRunMethodTakesAnArrayOfPromisesAsAParameter;
use Shasoft\Batch\Exceptions\BatchExceptionClosureForThenFunctionCannotUseExternalVariables;
use Shasoft\Batch\Exceptions\BatchExceptionInAGroupFunctionYouCanCreateAPromiseOnlyInTheSetResultFunction;

class ExceptionTest extends Base
{
    // Выполнить тест исключения
    public function exec(callable $cb, string $exceptionName): void
    {
        $hasErr = false;
        try {
            $cb();
            self::assertEquals(null, $exceptionName . '1. Должно быть сгенерировано исключение ' . $exceptionName);
            $hasErr = true;
        } catch (BatchException $e) {
            if (get_class($e) != $exceptionName) {
                self::assertEquals(get_class($e), $exceptionName, '2. Должно быть сгенерировано исключение ' . $exceptionName);
                $hasErr = true;
            }
        }
        if (!$hasErr) {
            $this->assertTrue(true);
        }
    }
    public function testReturnPromise(): void
    {
        $this->exec(function () {
            BatchManager::run(function () {
                return 123;
            });
        }, BatchExceptionRunMethodMustReturnAPromise::class);
    }
    public function testAll()
    {
        $this->exec(function () {
            BatchManager::run(function () {
                return BatchManager::All([
                    BatchTestFns::aaa(10),
                    'Строка',
                    BatchTestFns::bbb(10)
                ]);
            });
        }, BatchExceptionTheRunMethodTakesAnArrayOfPromisesAsAParameter::class);
    }
    public function testExecute()
    {
        $this->exec(function () {
            BatchManager::run(function () {
                return BatchTestFns::exception(10);
            });
        }, BatchExceptionInAGroupFunctionYouCanCreateAPromiseOnlyInTheSetResultFunction::class);
    }
    /*
    public function testPromiseUseExternalVariables()
    {
        $this->exec(function () {
            BatchManager::run(function () {
                $aaa = BatchTestFns::aaa(10);
                $aaa->then(function () use ($aaa) {
                    $aaa->then(function () {
                    });
                });
                return $aaa;
            });
        }, BatchExceptionClosureForThenFunctionCannotUseExternalVariables::class);
    }
    //*/
    /*
    // При добавлении исключения BatchExceptionClosureForThenFunctionCannotUseExternalVariables не ясно как можно сгенерировать эту ошибку
    public function testPromiseExecute()
    {
        $this->exec(function () {
            BatchManager::run(function () {
                $aaa = BatchTestFns::aaa(10);
                $aaa->then(function () use ($aaa) {
                    $aaa->then(function () {
                    });
                });
                return $aaa;
            });
        }, BatchExceptionPromiseAlreadyFulfilled::class);
    }
    //*/
}
