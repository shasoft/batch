<?php

namespace Shasoft\Batch\Tests\ApiTest;

use Shasoft\Batch\BatchGroup;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchGroupConfig;

class BatchTestFns
{
    static public function aaa($arg)
    {
        return BatchManager::createEx(function (BatchGroupConfig $groupConfig) {
            $groupConfig->setPriority(BatchManager::PRIORITY_LOW);
        }, function (BatchGroup $group) {
            $group->setResult(function ($arg) {
                return 'a' . $arg;
            });
        }, $arg);
    }
    static function bbb($arg)
    {
        return BatchManager::create(function (BatchGroup $group) {
            $group->setResult(function ($arg) {
                return 'b' . $arg;
            });
        }, $arg);
    }
    static function xxx($arg)
    {
        return BatchManager::create(function (BatchGroup $group) {
            $group->setResult(function ($arg) {
                return self::aaa('x(' . $arg . ')');
            });
        }, $arg);
    }
    static function exception($arg)
    {
        return BatchManager::create(function (BatchGroup $group) {
            self::aaa(12)->then(function ($value) use ($group) {
                $group->setResult(function ($arg) use ($value) {
                    return $arg . '@' . $value;
                });
            });
        }, $arg);
    }
}
