<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Redis;

/**
 * RedisClient
 */
class RedisClient
{
    private $redis_socket = false;
    //private $command = '';

    public function __construct($host='127.0.0.1', $port=6379, $timeout = 3) 
    {
        $this->redis_socket = stream_socket_client("tcp://".$host.":".$port, $errno, $errstr,  $timeout);
        if ( !$this->redis_socket ) {
            throw new Exception("{$errno} - {$errstr}");
        }
    }
}
