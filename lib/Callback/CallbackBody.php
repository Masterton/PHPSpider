<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Callback;

/**
 * CallbackBody
 */
class CallbackBody extends Callback
{
    public function __construct($paramList, $code, $param1 = null, $param2 = null, $param3 = null)
    {
    	$params = func_get_args();
    	$params = array_slice($params, 2);
    	$this->callback = create_function($paramList, $code);
    	$this->params = $params;
    }
}
