<?php
// +----------------------------------------------------------------------
// | PHPSpider [ A PHP Framework For Crawler ]
// +----------------------------------------------------------------------
// | Copyright (c) 1016-2018 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Master Zheng <zhengcloud@foxmail.com>
// +----------------------------------------------------------------------

//----------------------------------
// PHPSpider实用函数集合类文件
//----------------------------------

namespace PHPSpider\core;

// 引入PATH_DATA
require_once __DIR__ . '/constants.php';

class Util
{
    /**
     * 文件锁
     * 如果没有锁,就加一把锁并且执行逻辑,然后删除锁
     * if (!Util::lock('statistics_offer')) {
     *     Util::lock('statistics_offer');
     *     ...
     *     Util::unlock('statistics_offer');
     * } else { // 否则输出锁存在
     *     echo "process has been locked\n";
     * }
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 20:11:21
     */
    public static function lock($lock_name, $lock_timeout = 600)
    {
        $lock = Util::get_file(PATH_DATA . "/lock/{$lock_name}.lock");
        if ($lock) {
            $time = time() - $lock;
            // 还没有到10分钟,寿命进程还活着
            if ($time < $lock_timeout) {
                return true;
            }
            unlink(PATH_DATA . "/lock/{$lock_name}.lock");
        }
        Util::put_file(PATH_DATA . "/lock/{$lock_name}.lock", time());
        return false;
    }

    /**
     * unlock
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 20:10:50
     */
    public static function unlock($lock_name)
    {
        unlink(PATH_DATA . "/lock/{$lock_name}.lock");
    }

    /**
     * time2second
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 20:13:27
     */
    public static function time2second($time, $is_log = true)
    {
        if (is_numeric($time)) {
            $value = array(
                "years" => 0,
                "days" => 0,
                "hours" => 0,
                "minutes" => 0,
                "seconds" => 0,
            );
            if ($time >= 31556926) {
                $value["years"] = floor($time/31556926);
                $time = $time%31556926;
            }
            if ($time > 86400) {
                $value["days"] = floor($time/86400);
                $time = $time%86400;
            }
            if ($time >= 3600) {
                $value["hours"] = floor($time/3600);
                $time = $time%3600;
            }
            if ($time > 60) {
                $value['minutes'] = floor($time/60);
                $time = $time%60;
            }
            $value["seconds"] = floor($time);
            // return (array) $value;
            // $t = $value["years"] . "y " . $value["days"] . "d " . $value["hours"] . "h" . $value["minutes"] . "m " . $value["seconds"] . "s";
            if ($is_log) {
                $t = $value["days"] . "d " . $value["hours"] . "h" . $value["minutes"] . "m " . $value["seconds"] . "s";
            } else {
                $t = $value["days"] . "d " . $value["hours"] . "h" . $value["minutes"] . "minutes";
            }
            return $t;
        } else {
            return false;
        }
    }

    /**
     * get_days
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 20:24:44
     */
    public static function get_days($day_sta, $day_end = true, $range = 86400)
    {
        if ($day_end === true) {
            $day_end = date("y-m-d");
        }

        return array(function ($time) {return date("Y-m-d", $time);}, range(strtotime($day_sta), strtotime($day_end), $range));
    }

    /**
     * 获取文件行数
     *
     * @param mixed $filepath
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-15 20:28:14
     */
    public static function get_file_line($filepath)
    {
        $line = 0;
        $fp = fopen($filepath, 'r');
        if (!$fp) {
            return 0;
        }
        // 获取文件的一行内容,注意: 需要php5才支持该函数
        while (stream_get_line($fp, 8192, "\n")) {
            $line++;
        }
        fclose($fp); // 关闭文件
        return $line;
    }

    /**
     * 判断服务器是不是windows服务器
     *
     * @return boolean
     */
    public static function is_win()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === "WIN";
    }

    /**
     * 检查路径是否存在,不存在则递归生成路径
     *
     * @param mixed $path 路径
     * @static
     * @access public
     * @return bool or string
     */
    public static function path_exists($path)
    {
        $pathinfo = pathinfo($path . '/tmp.txt');
        if (!empty($pathinfo['dirname'])) {
            if (file_exists($pathinfo['dirname']) === false) {
                if (mkdir($pathinfo['dirname'], 0775, true) === false) {
                    return false;
                }
            }
        }
        return $path;
    }
}
