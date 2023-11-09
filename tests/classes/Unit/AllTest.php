<?php

namespace Shasoft\Batch\Tests\Unit;

use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\ApiTest\BatchTestFns;

class AllTest extends Base
{
    //*
    public function testAllThen1()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(1)
            ])->then(function(array $values) {
                return array_map(function($value){
                    return '#'.$value;
                },$values);
            });
        });
        self::assertValue(
            $ret,
            '["#a1"]'
        );
    }
    public function testAllThen2()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(1)
            ])->then(function(array $values) {
                return array_map(function($value){
                    return '#'.$value;
                },$values);
            })->then(function(array $values) {
                return array_map(function($value){
                    return $value.'$';
                },$values);
            });
        });
        self::assertValue(
            $ret,
            '["#a1$"]'
        );
    }    
    public function testAllEmpty1()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([]);
        });
        self::assertValue(
            $ret,
            '[]'
        );
    }
    public function testAllEmpty2()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([BatchManager::all([])]);
        });
        self::assertValue(
            $ret,
            '[[]]'
        );
    }
    public function testAllEmpty3()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchManager::all([]),
                BatchManager::all([]),
                BatchManager::all([])
            ]);
        });
        self::assertValue(
            $ret,
            '[[],[],[]]'
        );
    }
    public function testAllEmpty4()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchManager::all([
                    BatchManager::all([]),
                    BatchManager::all([BatchManager::all([])])
                ]),
                BatchManager::all([]),
                BatchManager::all([])
            ]);
        });
        self::assertValue(
            $ret,
            '[[[],[[]]],[],[]]'
        );
    }
    public function testAll1()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(1),
                BatchTestFns::aaa(2),
                BatchTestFns::aaa(3)
            ]);
        });
        self::assertValue(
            $ret,
            '["a1","a2","a3"]'
        );
    }
    public function testAll2()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(1),
                BatchTestFns::bbb(2),
                BatchTestFns::aaa(3),
                BatchTestFns::bbb(4)
            ]);
        });
        self::assertValue(
            $ret,
            '["a1","b2","a3","b4"]'
        );
    }
    public function testAll3()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(1),
                BatchManager::all([
                    BatchTestFns::aaa(5),
                    BatchTestFns::bbb(6),
                    BatchTestFns::aaa(7)
                ]),
                BatchTestFns::aaa(3),
                BatchTestFns::bbb(4)
            ]);
        });
        self::assertValue(
            $ret,
            '["a1",["a5","b6","a7"],"a3","b4"]'
        );
    }
    public function testAll4()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(1)
                    ->then(function ($value) {
                        return BatchTestFns::aaa('@' . $value);
                    })
                    ->then(function ($value) {
                        return BatchTestFns::aaa('#' . $value);
                    }),
            ]);
        });
        self::assertValue(
            $ret,
            '["a#a@a1"]'
        );
    }
    public function testAll5()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchTestFns::aaa(10)
                    ->then(function ($value) {
                        return BatchTestFns::aaa('@' . $value);
                    })
                    ->then(function ($value) {
                        return BatchTestFns::bbb('1' . $value);
                    }),
                BatchTestFns::bbb(20)
                    ->then(function ($value) {
                        return BatchTestFns::bbb('@' . $value);
                    })
                    ->then(function ($value) {
                        return BatchTestFns::aaa('2' . $value);
                    }),
            ]);
        });
        self::assertValue(
            $ret,
            '["b1a@a10","a2b@b20"]'
        );
    }
    public function testAll6()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                BatchManager::all([
                    BatchTestFns::aaa(10)
                        ->then(function ($value) {
                            return BatchTestFns::aaa('@' . $value);
                        })
                        ->then(function ($value) {
                            return BatchTestFns::bbb('1' . $value);
                        }),
                    BatchTestFns::bbb(20)
                        ->then(function ($value) {
                            return BatchTestFns::bbb('@' . $value);
                        })
                        ->then(function ($value) {
                            return BatchTestFns::aaa('2' . $value);
                        }),
                ]),
                BatchTestFns::bbb(30)
                    ->then(function ($value) {
                        return BatchTestFns::bbb('@' . $value);
                    })
                    ->then(function ($value) {
                        return BatchTestFns::aaa('3' . $value);
                    }),

            ]);
        });
        self::assertValue(
            $ret,
            '[["b1a@a10","a2b@b20"],"a3b@b30"]'
        );
    }
    public function testAll7()
    {
        $ret = BatchManager::run(function () {
            return BatchManager::all([
                'I' => BatchTestFns::aaa(1),
                'II' => BatchTestFns::aaa(2),
                'III' => BatchTestFns::bbb(3),
                'IV' => BatchTestFns::bbb(4),
                'V' => BatchTestFns::aaa(5)
            ]);
        });
        self::assertValue(
            $ret,
            '["I"=>"a1","II"=>"a2","III"=>"b3","IV"=>"b4","V"=>"a5"]'
        );
    }
    //*/
}
