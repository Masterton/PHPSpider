<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Callback;

/**
 * CallbackParameterToReference
 */
class CallbackParameterToReference extends Callback
{
    /**
     * @param $reference
     * @TODO implement $paramIndex;
     * param index choose which callback param will be passed to reference
     */
    public function __construct(&$reference)
    {
    	$this->callback = &$reference;
    }
}
