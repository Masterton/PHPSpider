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
// PHPSpider Curl操作类(Worker多进程操作类)
//----------------------------------

namespace PHPSpider\Library;

class Curl
{
	protected static $timeout = 10;
	protected static $ch = null;
	protected static $useragent = 'Mozilla/5.0 (Macintosh; Inter Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrom/44.0.2403.89 Safari/537.36';
	protected static $http_raw = false;
	protected static $cookie = null;
	protected static $cookie_jar = null;
	protected static $cookie_file = null;
	protected static $referer = null;
	protected static $ip = null;
	protected static $proxy = null;
	protected static $headers = array();
	protected static $hosts = array();
	protected static $gzip = false;
	protected static $info = array();

	/**
	 * 设置 timeout
	 *
	 * @param mixed $timeout
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:22:23
	 */
	public static function set_timeout($timeout)
	{
		self::$timeout = $timeout;
	}

	/**
	 * 设置代理
	 *
	 * @param mixed $proxy
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:23:41
	 */
	public static function set_proxy($proxy)
	{
		self::$proxy = $proxy;
	}

	/**
	 * 设置 referer
	 *
	 * @param mixed $referer
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:25:03
	 */
	public static function set_referer($referer)
	{
		self::$referer = $referer;
	}

	/**
	 * 设置 user_agent
	 *
	 * @param string $useragent
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:26:40
	 */
	public static function set_useragent($useragent)
	{
		self::$useragent = $useragent;
	}

	/**
	 * 设置 COOKIE
	 *
	 * @param string $cookie
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:28:01
	 */
	public static function set_cookie($cookie)
	{
		self::$cookie = $cookie;
	}

	/**
	 * 设置 COOKIE JAR
	 *
	 * @param string $cookie_jar
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:29:30
	 */
	public static function set_cookie_jar($cookie_jar)
	{
		self::$cookie_jar = $cookie_jar;
	}

	/**
	 * 设置 COOKIE FILE
	 *
	 * @param string $cookie_file
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:31:09
	 */
	public static function set_cookie_file($cookie_file)
	{
		self::$cookie_file = $cookie_file;
	}

	/**
	 * 获取内容的时候是不是连header也一起获取
	 *
	 * @param mixed $http_raw
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:34:21
	 */
	public static function set_http_raw($http_raw)
	{
		self::$http_raw = $http_raw;
	}

	/**
	 * 设置 IP
	 *
	 * @param string $ip
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:35:50
	 */
	public static function set_ip($ip)
	{
		self::$ip = $ip;
	}

	/**
	 * 设置 Headers
	 *
	 * @param string $headers
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:36:52
	 */
	public static function set_headers($headers)
	{
		self::$headers = $headers;
	}

	/**
	 * 设置 Hosts
	 *
	 * @param string $headers
	 * @return void
	 * @author Masterton Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:38:09
	 */
	public static function set_hosts($hosts)
	{
		self::$hosts = $hosts;
	}

	/**
	 * 设置 Gzip
	 *
	 * @param string $hosts
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:39:44
	 */
	public static function set_gzip($gzip)
	{
		self::$gzip = $gzip;
	}

	/**
	 * 初始化 CURL
	 *
	 * @return void
	 * @author Masterton <zhengcloud@foxmail.com>
	 * @time 2018-4-18 21:40:50
	 */
	public static function init()
	{
		// TODO
	}
}
