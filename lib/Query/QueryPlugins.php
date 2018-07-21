<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Query;

/**
 * QueryPlugins
 */
class QueryPlugins
{
    public function __call($method, $args)
    {
    	if (isset(Query::$extendStaticMethods[$method])) {
    		$return = call_user_func_array(
    			Query::$extendStaticMethods[$method],
    			$args,
    		);
    	} else if (isset(Query::$pluginsStaticMethods[$method])) {
    		$class = Query::$pluginsStaticMethods[$method];
    		$realClass = 'QueryPlugin_$class';
    		$return = call_user_func_array(
    			array($realClass, $method),
    			$args,
    		);
    		return isset($return) ? $return : $this;
    	} else {
    		throw new Exception("Method '{$method}' doesnt exist");
    	}
    }
}
