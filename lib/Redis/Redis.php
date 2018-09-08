<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Redis;

/**
 * Redis
 */
class Redis
{
    /**
     * redis链接标识符号
     */
    protected static $redis = NULL;

    /**
     * redis配置数组
     */
    protected static $configs = array();
    private static $links = array();
    private static $link_name = 'default';

    /**
     * 默认redis前缀
     */
    public static $prefix = "phpspider";

    public static $error = "";

    public static function init()
    {
        if (!extension_loaded("redis")) {
            self::$error = "The redis extension was not found";
            return false;
        }

        // 获取配置
        $config = self::$link_name == 'default' ? self::_get_default_config() : self::$configs[self::$link_name];

        // 如果当前链接标识符为空，或者ping不同，就close之后重新打开
        //if ( empty(self::$links[self::$link_name]) || !self::ping() )
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
     */
    public static function clear_link()
    {
        if (self::$links) {
            foreach(self::$links as $k => $v) {
                $v->close();
                unset(self::$links[$k]);
            }
        }
    }

    /**
     * set_connect
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
        // if ( !empty(self::$links[self::$link_name]) ) {
            // self::$links[self::$link_name]->close();
            // self::$links[self::$link_name] = null;
        // }
    }

    /**
     * set_connect_default
     */
    public static function set_connect_default()
    {
        $config = self::_get_default_config();
        self::set_connect('default', $config);
    }

    /**
    * 获取默认配置
    */
    protected static function _get_default_config()
    {
        if (empty(self::$configs['default'])) {
            if (!is_array($GLOBALS['config']['redis'])) {
                exit('cls_redis.php _get_default_config()' . '没有redis配置');
                // You not set a config array for connect\nPlease check the configuration file config/inc_config.php
            }
            self::$configs['default'] = $GLOBALS['config']['redis'];
        }
        return self::$configs['default'];
    }

    /**
     * set
     * 
     * @param mixed $key    键
     * @param mixed $value  值
     * @param int $expire   过期时间，单位：秒
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function set($key, $value, $expire = 0)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                if ($expire > 0) {
                    return self::$links[self::$link_name]->setex($key, $expire, $value);
                } else {
                    return self::$links[self::$link_name]->set($key, $value);
                }
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::set($key, $value, $expire);
            }
        }
        return NULL;
    }

    /**
     * set
     * 
     * @param mixed $key    键
     * @param mixed $value  值
     * @param int $expire   过期时间，单位：秒
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function setnx($key, $value, $expire = 0)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                if ($expire > 0) {
                    return self::$links[self::$link_name]->set($key, $value, array('nx', 'ex' => $expire));
                    //self::$links[self::$link_name]->multi();
                    //self::$links[self::$link_name]->setNX($key, $value);
                    //self::$links[self::$link_name]->expire($key, $expire);
                    //self::$links[self::$link_name]->exec();
                    //return true;
                } else {
                    return self::$links[self::$link_name]->setnx($key, $value);
                }
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::setnx($key, $value, $expire);
            }
        }
        return NULL;
    }
}
