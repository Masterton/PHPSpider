<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Callback;

/**
 * CallbackReference
 */
class CallbackReference extends Callback implements ICallbackNamed
{
    /**
     * @param $reference
     * @param $paramIndex
     * @todo implement $paramIndex; param index choose which callback param will be passed to reference
     */
    public function __construct(&$reference, $name = null)
    {
        $this->callback = &$reference;
    }
}
