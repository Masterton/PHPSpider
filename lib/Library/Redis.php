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
// PHPSpider Redis操作类
//----------------------------------

namespace PHPSpider\Library;

class Redis
{
	/**
	 * redis 链接标识符号
	 */
	protected static $redis = NULL;

	/**
	 * redis 配置数组
	 */
	protected static $configs = array();
	private static $links = array();
	private static $link_name = 'default';

	/**
	 * 默认 redis 前缀
	 */
	public static $prefix = "phpspider";

	public static $error = "";

	/**
	 * 初始化
	 *
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-20 20:53:09
	 */
	public static function init()
	{
		if (!extension_loaded("redis")) {
			self::$error = "The redis extension was not found";
			return false;
		}

		// 获取配置
		$config = self::$link_name == 'default' ? self::_get_default_config() : self::$configs[self::$link_name];

		// 如果当前链接标识符为空,或者ping不同,就close之后重新打开
		// if (empty(self::$links[self::$link_name]) || !self::ping()) {
		if (empty(self::$links[self::$link_name])) {
			self::$links[self::$link_name] = new Redis();
			if (!self::$links[self::$link_name]->connect($config['host'], $config['port'], $config['timeout'])) {
				self::$error = "Unable to connect to redis server\nPlease check the configuration file config/inc_config.php";
				unset(self::$links[self::$link_name]);
				return false;
			}

			// 验证
			if ($config['pass']) {
				if (!self::$links[self::$link_name]->auth($config['pass'])) {
					self::$error = "Redis Server authentication failed\nPlease check the configuration file config/inc_config.php";
					unset(self::$links[self::$link_name]);
					return false;
				}
			}

			$prefix = empty($config['prefix']) ? self::$prefix : $config['prefix'];
			self::$links[self::$link_name]->setOption(Redis::OPT_PREFIX, $prefix . ":");
			self::$links[self::$link_name]->setOption(Redis::OPT_READ_TIMEOUT, -1);
			self::$links[self::$link_name]->select($config['db']);
		}

		return self::$links[self::$link_name];
	}

	/**
	 * clear_link
	 *
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-20 21:04:36
	 */
	public static function clear_link()
	{
		if (self::$links) {
			foreach (self::$links as $k => $v) {
				$v->close();
				unset(self::$links[$k]);
			}
		}
	}
}
