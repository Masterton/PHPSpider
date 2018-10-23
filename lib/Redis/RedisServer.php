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

    public function __construct($host="0.0.0.0", $port=6379)
    {
        $this->socket = stream_socket_server("tcp://".$host.":".$port,$errno, $errstr);
        if (!$this->socket) die($errstr."--".$errno);
        echo "listen $host $port \r\n";
    }
}
