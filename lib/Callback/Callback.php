<?php

interface ICallbackNamed
{
    function hasName();
    function getName();
}

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Callback;

/**
 * Callback
 */
class Callback implements ICallbackName
{
    public $callback = null;
    public $params = null;
    protected $name;

    public function __construct($callback, $param1 = null, $param2 = null, $param3 = null)
    {
        $params = func_get_args();
        $params = array_slice($params, 1);
        if ($callback instanceof Callback) {
            // TODO implement recurention
        } else {
            $this->callback = $callback;
            $this->params = $params;
        }
    }

    public function getName()
    {
        return 'Callback: ' . $this->name;
    }

    public function hasName()
    {
        return isset($this->name) && $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    // TODO test me
    // public function addParams()
    // {
    //     $params = func_get_args();
    //     return new Callback($this->callback, $this->params + $params);
    // }
}
