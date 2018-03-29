<?php
// +----------------------------------------------------------------------
// | PHPSpider [ A PHP Framework For Crawler ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Master Zheng <zhengcloud@foxmail.com>
// +----------------------------------------------------------------------

//----------------------------------
// PHPSpider Redis操作类文件
//----------------------------------

namespace PHPSpider\core;

use Redis;
use Exception;

class Queue
{
    /**
     * redis 链接标识符号
     *
     */
    protected static $redis = NULL;

    /**
     * redis 配置数组
     *
     * @var array
     */
    protected static $configs = array();
    private static $links = array();
    private static $link_name = 'default';

    /**
     * 默认 redis 前缀
     *
     * @var string
     */
    public static $prefix = "phpspider";

    /**
     * 错误描述
     *
     * @var string
     */
    public static $error = "";

    /**
     * 初始化
     *
     */
    public static function init()
    {
        // 检查 redis 扩展是否已经加载
        if (!extension_loaded("redis")) {
            self::$error = "The redis extension was not found";
            return false;
        }

        // 获取配置
        $config = self::$link_name == 'default' ? self::_get_default_config() : self::$configs[self::$link_name];

        // 如果当前连接标识符为空,或者ping不动,就close之后重新打开
        if (empty(self::$links[self::$link_name])) {
            self::$links[self::$link_name] = new Redis();
            if (!self::$links[self::$link_name]->connect($config['host'], $config['port'], $config['timeout'])) {
                self::$error = "Unable to connect to redis server";
                unset(self::$links[self::$link_name]);
                return false;
            }

            // 验证
            if ($config['pass']) {
                if (!self::$links[self::$link_name]->auth($config['pass'])) {
                    self::$error = "Redis Server authentication failed";
                    unset(self::$links[self::$link_name]);
                    return false;
                }
            }

            $prefix = empty($config['prefix']) ? self::$prefix : $config['prefix'];
            self::$links[self::$link_name]->setOption(Redis::OPT_PREFIX, $prefix . ":");
            // 永不超时
            // ini_set('default_socket_timeout'); 无效，要用下面的做法
            self::$links[self::$link_name]->setOption(Redis::OPT_READ_TIMEOUT, -1);
            self::$links[self::$link_name]->select($config['db']);
        }

        return self::$links[self::$link_name];
    }

    /**
     * 设置(连接)配置
     *
     */
    public static function set_connect($link_name, $config = array())
    {
        self::$link_name = $link_name;
        if (!empty($config)) {
            self::$configs[self::$link_name] = $config;
        } else {
            if (empty(self::$configs[self::$link_name])) {
                throw new Exception("You not set a config array for connect!");
            }
        }
        // print_r(self::$configs);

        // 先断开原来的连接
        /*if (!empty(self::$links[self::$link_name])) {
            self::$links[self::$link_name]->close();
            self::$links[self::$link_name] = null;
        }*/
    }

    /**
     * 设置默认(连接)配置
     *
     */
    public static function set_connect_default()
    {
        $config = self::_get_default_config();
        self::set_connect('default', $config);
    }

    /**
     * 获取默认配置
     *
     */
    protected static function _get_default_config()
    {
        if (empty(self::$configs['default'])) {
            if (!is_array($GLOBALS['config']['redis'])) {
                exit("cls_redis.php _get_default_config()" . "没有reids配置");
            }
            self::$configs['default'] = $GLOBALS['config']['redis'];
        }
        return self::$configs['default'];
    }
}
