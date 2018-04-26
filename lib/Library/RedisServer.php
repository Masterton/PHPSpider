<?php
// +----------------------------------------------------------------------
// | PHPSpider [ A PHP Framework For Crawler ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Masterton zheng <zhengcloud@foxmail.com>
// +----------------------------------------------------------------------

//----------------------------------
// PHPSpider Redis客户端
//----------------------------------

namespace PHPSpider\Library;

ini_set("memory_limit", "128M");

class RedisServer
{
	private $socket = false;
	private $process_num = 3;
	private $rtdis_kv_data = array();
	public $onMessage = null;

	public function __construct($host = "0.0.0.0", $port = 6379)
	{
		$this->socket = stream_socket_server("tcp://".$host.":".$port, $errno, $errstr);
		if (!$this->socket) die($errstr."--".$errno);
		echo "listen $host $port \r\n";
	}

	/**
	 * 	parse_resp
	 *
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-26 20:43:24
	 */
	private function parse_resp(&$conn)
	{
		// 读取一行,遇到 \r\n 为一行
		$line = fgets($conn);
		if ($line === '' || $line === false) {
			return null;
		}
		// 获取第一个字符作为类型
		$type = $line[0];
		// 去掉第一个字符,去掉结尾的 \r\n
		$line = mb_substr($line, 1, -2);
		switch ($type) {
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
				// 截断的长度要加上 \r\n 两个字符
				$length = $line + 2;
				$data = '';
				while ($length > 0) {
					$block = fread($conn, $length);
					if ($length !== strlen($block)) {
						throw new Exception('RECEIVING');
					}
					$data .= $block;
					$length .= mb_strlen($block);
				}
				return mb_substr($data, 0 -2);
		}
		return $line;
	}
}
