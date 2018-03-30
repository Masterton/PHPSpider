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

    /**
     * set 设置对应键的值
     *
     * @param mixed $key   键
     * @param mixed $value 值
     * @param int $expire  过期时间,单位: 秒
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 15:44:18
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
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
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
     * setnx
     * @param mixed $key   键
     * @param mixed $value 值
     * @param int $expire  过期时间,单位: 秒
     * @return void
     * @author Master <zhengcloud@foxmail.com>
     * @time 2018-3-29 16:10:01
     */
    public static function setnx($key, $value, $expire)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                if ($expire > 0) {
                    return self::$links[self::$link_name]->set($key, $value, array('nx', 'ex' => $expire));
                    // self::$links[self::$link_name]->multi();
                    // self::$links[self::$link_name]->setNX($key, $value);
                    // self::$links[self::$link_name]->expire($key, $value);
                    // self::$links[self::$link_name]->exec();
                    // return false;
                } else {
                    return self::$links[self::$link_name]->setnx($key, $value);
                }
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::setnx($key, $value, $expire);
            }
        }
        return NULL;
    }

    /**
     * get 拿取数据
     *
     * @param mixed $key 键
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 16:21:07
     */
    public static function get($key)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->get($key);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::get($key);
            }
        }
        return NULL;
    }

    /**
     * del 删除数据
     *
     * @param mixed $key
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 16:26:41
     */
    public static function del($key)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->del($key);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::del($key);
            }
        }
    }

    /**
     * type 返回值的类型
     *
     * @param mixed $key
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 16:59:06
     */
    public static function type($key)
    {
        self::init();

        $types = array(
            '0' => 'set',
            '1' => 'string',
            '2' => 'list',
        );

        try {
            if (self::$links[self::$link_name]) {
                $type = self::$links[self::$link_name]->type($key);
                if (isset($types[$type])) {
                    return $types[$type];
                }
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::type($key);
            }
        }
        return NULL;
    }

    /**
     * incr 名称为key的string增加integer,integer为0则增1
     *
     * @param mixed $key
     * @param int $integer
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 17:05:56
     */
    public static function incr($key, $integer = 0)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                if (empty($integer)) {
                    return self::$links[self::$link_name]->incr($key);
                } else {
                    return self::$links[self::$link_name]->incrby($key, $integer);
                }
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::incr($key, $integer);
            }
        }
    }

    /**
     * flushdb 删除当前选择数据库中的所有key
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 16:50:28
     */
    public static function flushdb()
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->flushdb();
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::flushdb();
            }
        }
        return NULL;
    }

    /**
     * flushall 删除所有数据苦衷的所有key
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 16:56:08
     */
    public static function flushall()
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->flushall();
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::flushall();
            }
        }
        return NULL;
    }

    /**
     * 锁
     * 默认锁1秒
     *
     * @param mixed $name 锁的标识名
     * @param mixed $value 锁的值,貌似没啥意义
     * @param int $expire 当前锁的最大生存时间(秒),必须大于0,超过生存时间系统会自动强制释放
     * @param int $interval 获取锁失败后挂起再试的时间间隔(微秒)
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 16:36:37
     */
    public static function lock($name, $value = 1, $expire = 5, $interval = 100000)
    {
        if ($name == null) return false;

        self::init();
        try {
            if (self::$links[self::$link_name]) {
                $key = "Lock:{$name}";
                while (true) {
                    $result = self::$links[self::$link_name]->set($key, $value, array('nx', 'ex' => $expire));
                    if ($result != false) {
                        return true;
                    }

                    usleep($interval);
                }
                return false;
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::lock($name, $value, $expire, $interval);
            }
        }
    }

    /**
     * 解锁
     *
     * @param mixed $name 锁的标识
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 16:48:06
     */
    public static function unlock($name)
    {
        $key = "Lock:{$name}";
        return self::del($key);
    }

    /**
     * 删除连接的redis实例
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 17:04:29
     */
    public static function clear_link()
    {
        if (self::$links) {
            foreach (self::$links as $key => $value) {
                $value->close();
                unset(self::$links[$key]);
            }
        }
    }

    /**
     * keys
     * 查找符合给定模式的key
     * KEYS *命中数据库中所有key
     * KEYS h?llo命中hello hallo and hxllo等
     * KEYS h*llo命中hllo和heeeello等
     * KEYS h[ae]llo命中hello和hallo,但不命中hillo
     * 特殊符号用"\"隔开
     * 因为这个类加了OPT_PREFIX前缀,所以并不能真的列出redis所有的key,需要的话,要把前缀去掉
     *
     * @param mixed $key 键
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 17:20:00
     */
    public static function keys($key)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->keys($key);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::keys($key);
            }
        }
        return NULL;
    }

    /**
     * lpush 将数据从左边压入
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 11:02:44
     */
    public static function lpush($key, $value)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->lpush($key, $value);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::lpush($key, $value);
            }
        }
        return NULL;
    }

    /**
     * rpush 将数据从右边压入
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 14:08:23
     */
    public static function rpush($key, $value)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->rpush($key, $value);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::rpush($key, $value);
            }
        }
        return NULL;
    }

    /**
     * lpop 从左边弹出数据,并删除数据
     *
     * @param mixed $key
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 14:14:05
     */
    public static function lpop($key)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->lpop($key);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::lpop($key);
            }
        }
        return NUll;
    }

    /**
     * rpop 从右边弹出数据,并删除数据
     *
     * @param mixed $key
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 14:19:42
     */

    public static function rpop($key)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->rpop($key);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0)  {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::rpop($key);
            }
        }
        return NULL;
    }

    /**
     * lsize 队列长度,同llen
     *
     * @param mixed $key
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 14:25:31
     */
    public static function lsize($key)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->lSize();
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::lsize($key);
            }
        }
        return NULL;
    }

    /**
     * exists key值是否存在
     *
     * @param mixed $key
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 16:57:21
     */
    public static function exists($key)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->exists($key);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '". $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::exists($key);
            }
        }
        return false;
    }
}
