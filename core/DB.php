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
// PHPSpider数据库类文件
//----------------------------------

namespace PHPSpider\core;

class DB
{
	private static $configs = array();
	private static $rsid;
	private static $links = array();
	private static $link_name = 'default';

	/**
	 * 初始化mysql
	 *
	 * @return void
	 * @author Masterton <zhengcloud@foxamil.com>
	 * @time 2018-4-12 21:25:10
	 */
	public static function init_mysql()
	{
		// 获取配置
		$configs = self::$link_name == 'default' ? self::_get_default_config() : self::$config[self::$link_name];

		// 创建连接
		if (empty(self::$links[self::$link_name]) || empty(self::$links[$link_name]['conn'])) {
			// 第一次连接,初始化fail和pid
			if (empty(self::$links[self::$link_name])) {
				self::$links[self::$link_name]['fail'] = 0;
				self::$links[self::$link_name]['pid'] = function_exists('posix_getpid') ? posix_getpid() : 0;
				// echo "progress[" . self::$links[self::$link_name]['pid'] . "] create db connect[" . self::$link_name . "]";
			}
			self::$links[self::$link_name]['conn'] = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name'], $config['port']);
			if (mysqli_connect_errno()) {
				self::$links[self::$link_name]['fail']++;
				$errmsg = "Mysql Connect failed[" . self::$links[self::$link_name]['fail'] . ']: ' . mysqli_connect_error();
				echo Util::colorize(date("H:i:s") . " {$errmsg}\n\n", 'fail');
				Log::add($errmsg, "Error");
				// 连续失败5次,中断进程
				if (self::$links[self::$link_name]['fail'] >= 5) {
					exit(250);
				}
				self::init_mysql($config);
			} else {
				mysql_query(self::$links[self::$link_name]['conn'], "SET character_set_connection=utf-8, character_set_results=utf-8, character_set_client=binary, sql_mod='' ");
			}
		} else {
			$curr_pid = function_exists('posix_getpid') ? posix_getpid() : 0;
			// 如果父进程已经生成资源就释放重新生成,因为多进程不能共享连接资源
			if (self::$links[self::$link_name]['pid'] != $curr_pid) {
				self::clear_link();
			}
		}
	}

	/**
	 * 重新设置连接
	 * 传空的话就等于甘比数据库再连接
	 * 在多进程环境下如果主进程已经调用过了,子进程一定要调用一次 clear_link,否则会报错:
	 * Error while reading greeting packet. PID=19615
	 * 这是两个进程互抢一个连接句柄引起的
	 *
	 * @param array $config
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-12 21:44:05
	 */
	public static function clear_link()
	{
		if (self::$links) {
			foreach (self::$links as $k => $v) {
				@mysql_close($v['conn']);
				unset(self::$links[$k]);
			}
		}
		// 注意: 只会连接最后一个,不过毛事也够用了啊
		self::init_mysql();
	}
}
