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

    /**
     * 改变链接为指定配置的链接(吐过不同时使用多个数据库,不会涉及这个操作)
     *
     * @param $link_name 连接标识符
     * @param $config 多次使用时,一个数组只能传递一次
     *        $config 格式与 $GLOBALS['config']['db'] 一致
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 20:41:09
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
    }

    /**
     * 还原为默认连接(如果不同时使用多个数据库,不会涉及这个操作)
     *
     * @param $config 指定配置(默认使用inc_config.php配置)
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 20:44:55
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
     * @time 2018-4-13 20:48:12
     */
    protected static function _get_default_config()
    {
        if (empty(self::$configs['default'])) {
            if (!is_array($GLOBALS['config']['db'])) {
                exit('db.php _get_default_config()' . '没有mysql配置');
            }
            self::$configs['default'] = $GLOBALS['config']['db'];
        }
        return self::$configs['default'];
    }

    /**
     * 返回查询游标
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 20:52:48
     */
    protected static function _get_rsid($rsid = '')
    {
        return $rsid == '' ? self::$rsid : $rsid;
    }

    /**
     * autocommit
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 20:54:20
     */
    public static function autocommit($mode = false)
    {
        // self::$links[self::$link_name]['conn'] = self::init_mysql();
        // $int = $mode ? 1 : 0;
        // return @mysql_query(self::$links[self::$link_name]['conn'], "SET autocommit={$int}");
        self::init_mysql();
        return mysqli_autocommit(self::$links[self::$link_name]['conn'], $mode);
    }

    /**
     * begin_tran
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 20:59:01
     */
    public static function begin_tran()
    {
        // self::$links[self::$link_name]['conn'] = self::init_mysql(true);
        // return @mysqli_query(self::$links[self::$link_name]['conn'], 'BEGIN');
        return self::autocommit(false);
    }

    /**
     * commit
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 21:01:39
     */
    public static function commit()
    {
        self::init_mysql();
        return mysqli_commit(self::$links[self::$link_name]['conn']);
    }

    /**
     * rollback
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 21:03:17
     */
    public static function rollback()
    {
        self::init_mysql();
        return mysqli_rollback(self::$links[self::$link_name]['conn']);
    }

    /**
     * 查询
     *
     * @param string $sql
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 21:05:21
     */
    public static function query($sql)
    {
        $sql = trim($sql);

        // 初始化数据库
        self::init_mysql();
        self::$rsid = @mysqli_query(self::$links[self::$link_name]['conn'], $sql);

        if (self::$rsid === false) {
            // 不要每次都ping,浪费流量浪费性能,z执行出错了才重新连接
            $errno = mysql_errno(self::$links[self::$link_name]['conn']);
            if ($errno == 2013 || $errno == 2006) {
                $errmsg = msqli_error(self::$links[self::$link_name]['conn']);
                Log::add($errmsg, "Error");

                @mysqli_close(self::$links[self::$link_name]['conn']);
                self::$links[self::$link_name]['conn'] = null;
                return self::query($sql);
            }

            $errmsg = "Query SQL: " . $sql;
            Log::add($errmsg, "Warning");
            $errmsg = "Error SQL: " . mysqli_error(self::$links[self::$link_name]['conn']);
            Log::add($errmsg, "Warning");

            $backtrace = debug_backtrace();
            array_shift($backtrace);
            $narr = array('class', 'type', 'function', 'file', 'line');
            $err = "debug_backtrace: \n";
            foreach ($backtrace as $i => $l) {
                foreach ($narr as $k) {
                    if (!isset($l[$k])) {
                        $l[$k] = '';
                    }
                }
                $err .= "[$i] in function {$l['class']}{$l['type']}{$l['function']}";
                if ($l['file']) $err .= " in {$l['file']} ";
                if ($l['line']) $err .= " on line {$l['line']} ";
                $err .= "\n";
            }
            Log::add($err);

            return false;
        } else {
            return self::$rsid;
        }
    }

    /**
     * fetch
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-13 21:29:34
     */
    public static function fetch($rsid)
    {
        $rsid = self::_get_rsid($rsid);
        $row = mysqli_fetch_array($rsid, MYSQLI_ASSOC);
        return $row;
    }
}
