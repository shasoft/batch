<?php

namespace Shasoft\Batch\Tests\ApiTest;

use Shasoft\Batch\BatchGroup;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchGroupConfig;

class BatchTestCacheFns
{
    public array $X;
    public array $Count = [
        'fnGet0' => 0,
        'fnPut0' => 0,
        'fnGet1' => 0,
        'fnPut1' => 0,
        'fnGet2' => 0,
        'fnPut2' => 0,
        'fnLifeTime' => 0,
        'fnPut3' => 0,
        'fnNone1' => 0
    ];
    // Конструктор
    public function __construct()
    {
        $this->xReset();
    }
    public function xReset(): void
    {
        $this->X = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];
    }
    //
    public function fnGet0(int $x): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCacheGet();
        }, function (BatchGroup $group) {
            $this->Count['fnGet0']++;
            $group->setResult(function (int $x) {
                $v = $this->X[0][$x] ?? 0;
                $this->fnPut0($x, $v);
                return $v;
            });
        }, $x);
    }
    //
    public function fnPut0(int $x, int $v): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCachePut(0);
        }, function (BatchGroup $group) {
            $this->Count['fnPut0']++;
            $group->setResult(function (int $x, int $v): void {
                $this->X[0][$x] = $v;
            });
        }, $x, $v);
    }
    //
    public function fnGet1(int $x): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCacheGet();
        }, function (BatchGroup $group) {
            $this->Count['fnGet1']++;
            $group->setResult(function (int $x) {
                $v = $this->X[1][$x] ?? 7;
                $this->fnPut1($x, $v);
                return $this->fnGet2($x)->then(function ($value) use ($v) {
                    return $value + $v;
                });
            });
        }, $x);
    }
    //
    public function fnPut1(int $x, int $v): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCachePut(0);
        }, function (BatchGroup $group) {
            $this->Count['fnPut1']++;
            $group->setResult(function (int $x, int $v): void {
                $this->X[1][$x] = $v;
            });
        }, $x, $v);
    }
    //
    public function fnGet2(int $x): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCacheGet();
        }, function (BatchGroup $group) {
            $this->Count['fnGet2']++;
            $group->setResult(function (int $x) {
                $v = $this->X[2][$x] ?? 10;
                $this->fnPut2($x, $v);
                return $x * $v;
            });
        }, $x);
    }
    //
    public function fnPut2(int $x, int $v): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCachePut(0);
        }, function (BatchGroup $group) {
            $this->Count['fnPut2']++;
            $group->setResult(function (int $x, int $v): void {
                $this->X[2][$x] = $v;
            });
        }, $x, $v);
    }
    //
    public function fnLifeTime(int $x): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCacheLifetime(5);
        }, function (BatchGroup $group) {
            $this->Count['fnLifeTime']++;
            $group->setResult(function (int $x) {
                $v = $this->X[3][$x] ?? 3;
                return $x * $v;
            });
        }, $x);
    }
    //
    public function fnPut3(int $x, int $v): BatchPromise
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setCachePut(0);
        }, function (BatchGroup $group) {
            $this->Count['fnPut3']++;
            $group->setResult(function (int $x, int $v) {
                $this->X[3][$x] = $v;
            });
        }, $x, $v);
    }
    //
    public function fnNone1(int $x): BatchPromise
    {
        return BatchManager::create(function (BatchGroup $group) {
            $this->Count['fnNone1']++;
            $group->setResult(function (int $x) {
                return $this->fnGet1($x)->then(function ($value) {
                    return 1000 + $value;
                });
            });
        }, $x);
    }
}
