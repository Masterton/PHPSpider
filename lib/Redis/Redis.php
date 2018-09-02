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
}
