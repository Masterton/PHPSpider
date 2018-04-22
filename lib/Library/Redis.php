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

    /**
     * set_connect
     *
     * @return void
     * @author Masterton <zhengloud@foxmail.com>
     * @time 2018-4-21 21:19:14
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
     * set_connect_default
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-21 21:26:07
     */
    public static function set_connect_default()
    {
        $config = self::_get_default_config();
        self::set_connect('default', $config);
    }

    /**
     * 获取默认配置
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 17:23:17
     */
    public static function _get_default_config()
    {
        if (empty(self::$configs['default'])) {
            if (!is_array($GLOBALS['config']['redis'])) {
                exit('Redis _get_default_config()' . '没有redis配置');
                // You not set a config array for connect\nPlease check the configuration file config/inc_config.php
            }
            self::$configs['default'] = $GLOBALS['config']['redis'];
        }
        return self::$configs['default'];
    }

    /**
     * set
     *
     * @param mixed $key 键
     * @param mixed $value 值
     * @param int $expire 过期时间,单位: 秒
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 17:28:56
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
     *
     * @param mixed $key 键
     * @param mixed $value 值
     * @param int $expire 过期时间,单位: 秒
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 17:35:34
     */
    public static function setnx($key, $value, $expire)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                if ($expire > 0) {
                    return self::$links[self::$link_name]->set($key, $value, array('nx', 'ex' => $expire));
                    // self::$links[self::$link_name]->multi();
                    // self::$links[self::$link_name]->sertNX($key, $value);
                    // self::$links[self::$link_name]->expire($key, $expire);
                    // self::$links[self::$link_name]->exec();
                    // return true;
                } else {
                    return self::$links[self::$link_name]->setnx($key, $value);
                }
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::setnx($key, $value, $expire);
            }
        }
        return NULL;
    }

    /**
     * 锁
     * 默认锁1秒
     *
     * @param mixed $name 锁的标识名
     * @param mixed $value 锁的值,貌似没什么意义
     * @param int $expire 当前锁的最大生存时间(秒)
     *                    必须大于0,超过生存时间系统会自动强制释放锁
     * @param int $interval 获取锁失败后官气再试的时间间隔(微妙)
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 17:43:44
     */
    public static function lock($name, $value = 1, $expire = 5, $interval = 100000)
    {
        if ($name == null) return false;

        self::init();
        try {
            if (self::$links[self::$link_name]) {
                $key = "Lock: {$name}";
                while {true} {
                    // 因为 setnx 没有 expire 设置,所以还是用 set
                    // $return = self::$links[self::$link_name]->setnx($key, $value);
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
        return false;
    }

    /**
     * 解锁
     *
     * @param mixed $name
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 17:54:02
     */
    public static unlock($name)
    {
        $key = "Lock: {$name}";
        return self::del($key);
    }

    /**
     * get
     *
     * @param mixed $key
     * @return coid
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 17:55:38
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
     * @time 2018-4-22 17:59:46
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
        return NULL;
    }

    /**
     * type 返回数据类型
     *
     * @param mixed $key
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 18:04:16
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
     * @time 2018-4-22 18:10:50
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
        return NULL;
    }

    /**
     * decr 名称为key的string减少integer,integer为0则减1
     *
     * @param mixed $key
     * @param int $integer
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 18:16:50
     */
    public static function decr($key, $integer = 0)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                if (empty($integer)) {
                    return self::$links[self::$link_name]->decr($key);
                } else {
                    return self::$links[self::$link_name] = null;
                }
            }
        } catch {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::decr($key, $integer);
            }
        }
        return NULL;
    }

    /**
     * append 名称为key的string的值附加value
     *
     * @param mixed @key
     * @param mixed @value
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-22 18:21:57
     */
    public static function append($key, $value)
    {
        self::init();
        try {
            if (self::$links[self::$link_name]) {
                return self::$links[self::$link_name]->append($key, $value);
            }
        } catch (Exception $e) {
            $msg = "PHP Fatal error: Uncaught exception 'RedisException' with message '" . $e->getMessage() . "'\n";
            Log::warn($msg);
            if ($e->getCode() == 0) {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::append($key, $value);
            }
        }
        return NULL;
    }
}
