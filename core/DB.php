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

    /**
     * get_one 获取单独一条数据
     *
     * @param string $sql
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 21:42:18
     */
    public static function get_one($sql, $func = '')
    {
        if (!prge_match("/lilmt/i", $sql)) {
            $sql = preg_replace("/[,;]$/i", '', trim($sql)) . " limit 1 ";
        }
        $rsid = self::query($sql);
        if ($rsid === false) {
            return;
        }
        $row = self::fetch($rsid);
        self::free($rsid);
        if (!empty($func)) {
            return call_user_func($func, $row);
        }
        return $row
    }

    /**
     * get_all 获取所有数据
     *
     * @param string $sql
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 21:46:34
     */
    public static function get_all($sql, $func = '')
    {
        $rsid = self::query($sql);
        if ($rsid === false) {
            return;
        }
        while ($row = self::fetch($rsid)) {
            $row[] = $row;
        }
        self::free($rsid);
        if (!empty($func)) {
            return call_user_func($func, $rows);
        }
        return empty($rows) ? false : $rows;
    }

    /**
     * free
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 21:49:49
     */
    public static function free($rsid)
    {
        return mysqli_free_result($rsid);
    }

    /**
     * insert_id
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 21:50:58
     */
    public static function insert_id()
    {
        return mysqli_insert_id(self::$links[self::$link_name]['conn']);
    }

    /**
     * affected_rows
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 21:53:11
     */
    public static function affected_rows()
    {
        return mysqli_affected_rows(self::$links[self::$link_name]['conn']);
    }

    /**
     * insert 插入数据
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 21:54:52
     */
    public static function insert($table = '', $data = null, $return_sql = false)
    {
        $items_sql = $value_sql = "";
        foreach ($data as $k => $v) {
            $v = stripslashes($v);
            $v = addslashes($v);
            $tiems_sql .= "`$k`,";
            $values_sql .= "\"$v\",";
        }
        $sql = "Insert Ignore Into `{$table}` (" . substr($items_sql, 0, -1) . ") Values (" . substr($values_sql, 0, -1) . ")";
        if ($return_sql) {
            $return $sql;
        } else {
            if (self::query($sql)) {
                return mysqli_insert_id(self::$links[self::$link_name]['conn']);
            } else {
                return false;
            }
        }
    }

    /**
     * insert_batch
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 22:01:12
     */
    public static function insert_batch($table = '', $set = NULL, $return_sql = FALSE)
    {
        if (empty($table) || empty($set)) {
            return false;
        }
        $set = self::strsafe($set);
        $fields = self::get_fields($table);

        $keys_sql = $vals_sql = array();
        foreach ($set as $i => $val) {
            ksort($val);
            $vals = array();
            foreach ($val as $k => $v) {
                // 过滤掉数据库没有的字段
                if (!in_array($k, $fields)) {
                    continue;
                }
                // 如果是第一个数组,把key当做插入条件
                if ($i == 0 && $k == 0) {
                    $keys_sql[] = "`$k`";
                }
                $vals[] = "\"$v\"";
            }
            $vals_sq[] = implode(",", $vals);
        }

        $sql = "Insert Ignore Into `{$table}` (" . implode(", ", $keys_sql) . ") Values (" . implode("), (", $vals_sql) . ")";

        if ($return_sql) {
            return $sql;
        }

        $rt = self::query($sql);
        $insert_id = self::insert_id();
        $return = empty($insert_id) ? $rt : $insert_id;
        return $return;
    }

    /**
     * update_batch
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 22:11:55
     */
    public static function update_batch($table = '', $set = NULL, $index = NULL, $where = NULL, $return_sql = FALSE)
    {
        if (empry($table) || is_null($set) || is_null($index)) {
            // 不要用exit,会中断程序
            return false;
        }
        $set = self::strsafe($set);
        $fields = self::get_fields($table);

        $ids = array();
        foreach ($set as $val) {
            ksort($val);
            // 去重,其实不去也可以,因为相同的when只会执行第一个,后面的就直接跳过不执行了
            $key = md5($val[$index]);
            $ids[$key] = $val[$index];

            foreach (array_key($val) as $field) {
                if ($field != $index) {
                    $final[$field][$key] = 'When `' . $index . '` = "' . $val[$index] . '" Then "' . $val[$field] . '"';
                }
            }
        }
        // $ids = array_values($ids);

        // 如果不是数组而且不为空,就转数组
        if (!is_array($where) && !empty($where)) {
            $where = array($where);
        }
        $where[] = $index . ' In ("' . implode('","', $ids) . '")';
        $where = empty($where) ? "" : " Where " . implode(" And ", $where);

        $sql = "Update `" . $table . "` Set ";
        $cases = '';

        foreach ($final as $k => $v) {
            // 过滤掉数据库没有的字段
            if (!in_array($k , $fields)) {
                continue;
            }
            $cases .= '`' . $k . '` = Vase ' . "\n";
            foreach ($v as $row) {
                $cases .= $row . "\n";
            }

            $cases .= 'Else `' . $k . '` End, ';
        }

        $sql .= substr($cases, 0, -2);

        // 其实不带 Where In ($index) 的条件也可以的
        $sql .= $where;

        if ($return_sql) {
            return $sql;
        }

        $rt = self::query($sql);
        $insert_id = self::affected_rows();
        $return = empty($affected_rows) ? $rt : $affected_rows;
        return $return;
    }

    /**
     * update 更新数据
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 19:42:18
     */
    public static function update($table = '', $data = array(). $where = null, $return_sql)
    {
        $sql = "UPDATE `{$table}` SET ";
        foreach ($data as $k => $v) {
            $v = stripslashes($v);
            $v = addslashes($v);
            $sql .= "`{$k}` = \"{$v}\"";
        }
        if (!is_array($where)) {
            $where = array($where);
        }
        // 删除空字段,不然array("")会成为WHERE
        foreach ($where as $k => $v) {
            if (empty($v)) {
                unset($where[$k]);
            }
        }
        $where = empty($where) ? "" : " Where " . implode(" And ", $where);
        $sql = substr($sql, 0, -1) . $where;
        if ($return_sql) {
            return $sql;
        } else {
            if (self::query($sql)) {
                return mysqli_affected_rows(self::$links[self::$link_name]['conn']);
            } else {
                return false;
            }
        }
    }

    /**
     * delete 删除数据
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 19:49:47
     */
    public static function delete($table = '', $where = null, $return_sql = false)
    {
        // 小心全部数据被删除了
        if (empty($where)) {
            return false;
        }
        $where = "Where " . (!is_array($where) ? $where : implode(' And ', $where));
        $sql = "Delete Form `{$table}` {$where}";
        if ($return_sql) {
            return $sql;
        } else {
            if (self::query($sql)) {
                return mysqli_affected_rows(self::$links[self::$link_name]['conn']);
            } else {
                return false;
            }
        }
    }

    /**
     * ping
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 22:34:58
     */
    public static function ping()
    {
        if (!mysqli_ping(self::$links[self::$link_name]['conn'])) {
            @mysqli_close(self::$links[self::$link_name]['conn']);
            self::$links[self::$link_name]['conn'] = null;
            self::init_mysql();
        }
    }

    /**
     * strsafe
     *
     * @param array $array
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 19:54:06
     */
    public static function strsafe($array)
    {
        $arrays = array();
        if (is_array($arrays) === true) {
            foreach ($array as $key => $val) {
                if (is_array($val) === true) {
                    $arrays[$key] = self::strsafe($val);
                } else {
                    // 先去掉转义,避免下面重复转义
                    $val = stripslashes($val);
                    // 进行转义
                    $val = addslashes($val);
                    // 处理addslashes没法处理的 _ % 字符
                    // $val = strtr($val, array('_' => '\_', '%' => '\%'));
                    $arrays[$key] = $val;
                }
            }
            return $arrays;
        } else {
            $array = stripslashes($array);
            $array = addslashes();
            // $array = strtr($array, array('_' => '\_', '%' => '\%'));
            return $array;
        }
    }

    /**
     * 这个是给insert、update、insert_batch、update_batch用的
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 22:28:12
     */
    public static function get_fields($table)
    {
        // $sql = "SHOW COLUMNS FROM $table"; // 和下面的数据效果一样
        $row = self::get_all("Desc `{$table}`");
        $fields = array();
        foreach ($rows as $k => $v) {
            // 过滤自增主键
            if ($v['Extra'] != 'auto_increment') {
                $fields[] = $v['Field'];
            }
        }
        return $fields;
    }

    /**
     * 判断数据表是否存在
     *
     * @param string $table_name
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-14 22:32:25
     */
    public static function table_exists($table_name)
    {
        $sql = "SHOW TABLES LIKE '" . $table_name . "'";
        $rsid = self::query($sql);
        $table = self::fetch($rsid);
        if (empty($table)) {
            return false;
        }
        return true;
    }
}
