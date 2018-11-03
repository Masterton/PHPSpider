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

    private function parse_resp(&$conn)
    {
        // 读取一行，遇到 \r\n 为一行
        $line = fgets($conn);
        if($line === '' || $line === false) {
            return null;
        }
        // 获取第一个字符作为类型
        $type = $line[0];
        // 去掉第一个字符，去掉结尾的 \r\n
        $line = mb_substr($line, 1, -2);
        switch ( $type ) {
            case "*":
                // 得到长度
                $count = (int) $line;
                $data = array();
                for ($i = 1; $i <= $count; $i++) {
                    $data[] = $this->parse_resp($conn);
                }
                return $data;
            case "$":
                if ($line == '-1') {
                    return null;
                }
                // 截取的长度要加上 \r\n 两个字符
                $length = $line + 2;
                $data = '';
                while ($length > 0) {
                    $block = fread($conn, $length);
                    if ($length !== strlen($block)) {
                        throw new Exception('RECEIVING');
                    }
                    $data .= $block;
                    $length -= mb_strlen($block);
                }
                return mb_substr($data, 0, -2);
        }
        return $line;
    }

    private function start_worker_process()
    {
        $pid = pcntl_fork();
        switch ($pid) {
            case -1:
                echo "fork error : {$i} \r\n";
                exit;
            case 0:
                while ( true ) {
                    echo  "PID ".posix_getpid()." waiting...\n";
                    // 堵塞等待
                    $conn = stream_socket_accept($this->socket, -1);
                    if ( !$conn ) {
                        continue;
                    }
                    //"*3\r\n$3\r\nSET\r\n$5\r\nmykey\r\n$7\r\nmyvalue\r\n"
                    while( true ) {
                        $arr = $this->parse_resp($conn);
                        if ( is_array($arr) ) {
                            if ($this->onMessage) 
                            {
                                call_user_func($this->onMessage, $conn, $arr);
                            }
                        } else if ( $arr ) {
                            if ($this->onMessage) 
                            {
                                call_user_func($this->onMessage, $conn, $arr);
                            }
                        } else {
                            fclose($conn);
                            break;
                        }
                    }
                }

            default:
                $this->pids[$pid] = $pid;
                break;
        }
    }

    public function run()
    {
        for($i = 1; $i <= $this->process_num; $i++) {
            $this->start_worker_process();
        }

        while( true ) {
            foreach ($this->pids as $i => $pid) {
                if($pid) {
                    $res = pcntl_waitpid($pid, $status,WNOHANG);
                    if ( $res == -1 || $res > 0 ) {
                        $this->start_worker_process();
                        unset($this->pids[$pid]);
                    }
                }
            }
            sleep(1);
        }
    }
}
