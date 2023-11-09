<?php

namespace Shasoft\Batch;

use Shasoft\CDump\CDumpLog;
use Shasoft\CDump\CDumpHtml;

// Лог
class BatchDebug
{
    // Контекст менеджера
    public BatchManagerContext $managerContext;
    // Контексты всех обещаний
    public array $promisesContext = [];
    // Контексты всех групп в очередности их выполнения
    public array $groupsContext = [];
    // Добавить в лог
    public function add(BatchPromiseContext|BatchGroupContext $object)
    {
        if ($object instanceof BatchPromiseContext) {
            $this->promisesContext[$object->id] = $object;
        } else if ($object instanceof BatchGroupContext) {
            $this->groupsContext[] = $object;
        }
    }
    // Получить список групп
    public function getGroups(): array
    {
        return array_map(function (BatchGroupContext $group) {
            return str_replace('\\', '.', $group->key);
        }, $this->groupsContext);
    }
    //
    static public function fnArgsKeyGenerate(): callable
    {
        return function (array $args) {
            /*
            s_dump(
                $args,
                json_encode($args, JSON_UNESCAPED_SLASHES),
                substr(json_encode($args, JSON_UNESCAPED_SLASHES), 1, -1)
            );
            //*/
            return str_replace("\\\\", "\\", substr(json_encode($args, JSON_UNESCAPED_SLASHES), 1, -1));
        };
    }

    //
    static protected function htmlUl(string $text): string
    {
        return  empty($text) ? '' : '<ul>' . $text . '</ul>';
    }
    //
    static protected function result(mixed $result, bool $hasEqual = true): string
    {
        $ret = $hasEqual ? ' <span style="color:plum">=</span> ' : '';
        return $ret . '<span style="color:indigo">' . CDumpHtml::to($result) . '</span>';
    }
    // Цвета для каждого типа обещания
    static array $colors = [
        BatchGroupContext::CACHE_NONE     => 'black',
        BatchGroupContext::CACHE_GET      => 'green',
        BatchGroupContext::CACHE_PUT      => 'red',
        BatchGroupContext::CACHE_LIFETIME => 'blue'
    ];
    // Получить дерево созданных обещаний
    static string $colorOp = 'magenta';
    static public function getHtmlPromise(array $promises, array $promisesId, int $id): string
    {
        $promiseContext = $promises[$id];
        if (is_null($promiseContext->groupContext)) {
            $prefix = '@';
            $cacheType = BatchGroupContext::CACHE_NONE;
        } else {
            $prefix = $promiseContext->groupContext->key;
            $cacheType = $promiseContext->groupContext->cacheType;
        }
        //
        if (is_null($promiseContext->groupContext)) {
            $suffix = '';
            //
            foreach ($promiseContext->args as $arg) {
                $suffix .= self::getHtmlPromiseTree($promises, $promisesId, $arg->id);
            }
            $suffix = self::result($promiseContext->result()) . self::htmlUl($suffix);
        } else {
            // Аргументы
            $suffix = CDumpHtml::args($promiseContext->args);
            if (!empty($suffix)) {
                $suffix = '<span style="color:' . self::$colorOp . '">(</span>' . $suffix . '<span style="color:' . self::$colorOp . '">)</span>';
            }
            // Результат
            $suffix .= '<span style="cursor:pointer" title="' . htmlentities(strip_tags(self::result($promiseContext->result(), false))) . '">' . self::result($promiseContext->result('group')) . '</span>';
        }
        // Цвет в зависимости от типа
        $color = self::$colors[$cacheType];
        //
        $cacheMode = '';
        if ($cacheType == BatchGroupContext::CACHE_GET) {
            switch ($promiseContext->cacheMode) {
                case BatchPromise::MODE_CACHE_OFF: {
                        $cacheMode = '<strong style="color:red" title="Off">#</strong>';
                    }
                    break;
                case BatchPromise::MODE_CACHE_DIRECT: {
                        $cacheMode = '<strong style="color:blue" title="Direct">*</strong>';
                    }
                    break;
                case BatchPromise::MODE_CACHE_ON: {
                        $cacheMode = '<strong style="color:green" title="On">+</strong>';
                    }
                    break;
            }
        }
        //
        return '<span style="color:gray">' . $prefix . '</span><strong style="color:' . $color . '">' . $promiseContext->id . '</strong>' . $cacheMode . $suffix;
    }
    // Получить дерево созданных обещаний
    static protected function getHtmlPromiseTree(array $promises, array $promisesId, int $id): string
    {
        $promiseContext = $promises[$id];
        $ret = '<li>';
        $ret .= self::getHtmlPromise($promises, $promisesId, $promiseContext->id);
        //
        $childs = $promisesId[$id];
        if (!empty($childs)) {
            $childsHtml = '';
            foreach ($childs as $idChild) {
                $childsHtml .= self::getHtmlPromiseTree($promises, $promisesId, $idChild);
            }
            $ret .= self::htmlUl($childsHtml);
        }
        $ret .= '</li>';
        return $ret;
    }
    // Получить дерево созданных обещаний
    public function getHtmlLog(): string
    {
        // Вывод дерева обещаний
        $root = [];
        $promises = [];
        $promisesId = [];
        //--
        foreach ($this->promisesContext as $promiseContext) {
            $root[$promiseContext->id] = $promiseContext;
            $promises[$promiseContext->id] = $promiseContext;
            $promisesId[$promiseContext->id] = [];
        }
        //--
        foreach ($this->promisesContext as $promiseContext) {
            if (!is_null($promiseContext->parent)) {
                $promisesId[$promiseContext->parent->id][] = $promiseContext->id;
                // Если есть родитель, то значит это не корневой элемент
                unset($root[$promiseContext->id]);
            }
            if (is_null($promiseContext->groupContext)) {
                // Если входит в all, то значит это не корневой элемент
                foreach ($promiseContext->args as $arg) {
                    unset($root[$arg->id]);
                }
            }
        }
        $htmlPromises = '';
        foreach ($root as $promiseContext) {
            $htmlPromises .= self::getHtmlPromiseTree($promises,  $promisesId, $promiseContext->id);
        }
        // Вывод истории выполнения групп
        $htmlGroups = '';
        foreach ($this->groupsContext as $groupContext) {
            $htmlGroups .= '<li><strong style="color:teal">' . $groupContext->priority . '</strong> &gt; <strong>' . $groupContext->key . '</strong>';
            $htmlGroupPromises = '';
            foreach ($groupContext->promisesContext as $promiseContext) {
                $htmlGroupPromises .= self::getHtmlPromiseTree($promises,  $promisesId, $promiseContext->id);
            }
            $htmlGroups .= self::htmlUl($htmlGroupPromises);
            $htmlGroups .= '</li>';
        }
        //
        return empty($htmlPromises . $htmlGroups) ? '' : self::htmlUl($htmlPromises) . '<hr/>' . self::htmlUl($htmlGroups);
    }
    //////////////////////////////////////////////////////////////////////////////////////////////
    // Установить режим логирования
    static bool $logEnable = false;
    static public function log(bool $enable): void
    {
        self::$logEnable = $enable;
        if ($enable) {
            CDumpLog::enable($enable);
        }
    }
    // Логирование активировано?
    static public function hasLog(): bool
    {
        return self::$logEnable;
    }
    // Установить лог
    static public ?BatchDebug $debug = null;
    static public function setDebug(?BatchDebug $debug): void
    {
        self::$debug = $debug;
    }
}
