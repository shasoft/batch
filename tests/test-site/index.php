<?php

use Shasoft\Batch\BatchDebug;
use Shasoft\Batch\BatchConfig;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\Tests\CacheDebug;
use Shasoft\Batch\Tests\ApiTest\BatchTestFns;

require_once __DIR__ . '/../classes/bootstrap.php';

// Включить логирование
BatchDebug::log(true);

class CacheTest
{
    protected int $key = 2;
    // Конструктор
    public function __construct()
    {
    }
    static public function assertEquals($a, $b)
    {
    }
    public function run()
    {
        $cacheDebug = new CacheDebug;
        $cacheDebug->run(
            [
                function (CacheDebug $dbg) {
                    return BatchManager::all([
                        BatchManager::all([]),
                        BatchManager::all([])
                    ]);                    
                    return BatchManager::all([BatchManager::all([])]);
                    //return BatchManager::all([]);
                    return BatchManager::all([
                        'A'=>BatchTestFns::aaa(1)
                    ])->then(function($values) {
                        s_dump($values);
                        return $values;
                    });
                },
                function (CacheDebug $dbg) :void{
                    s_dd($dbg);
                }                
            ],
            true,
            true
        );
        s_dd($cacheDebug);
    }
}

try {
    (new CacheTest)->run();
} catch (\Throwable $th) {
    s_dd($th);
}
