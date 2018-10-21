<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Redis;

/**
 * RedisServer
 */
class RedisServer
{
    private $socket = false;
    private $process_num = 3;
    public $redis_kv_data = array();
    public $onMessage = null;
}
