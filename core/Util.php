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
     * 获取表数
     *
     * @param mixed $table_name 表名
     * @param mixed $item_value 唯一索引
     * @param int $table_bun 表数量
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 19:29:53
     */
    public static function get_table_num($item_value, $table_num = 100)
    {
        // sha1: 返回一个40字符长度的16进制数字
        $item_value = sha1(strtolower($item_value));
        // base_convert: 进制间转换,下面是把16进制转成10进制,方便做除法运算
        // str_pad: 把字符串填充为指定的长度,下面是在左边加0,表数量大于100就3位,否则2位
        $step = $table_num > 100 ? 3 : 2;
        $item_value = str_pad(base_convert(substr($item_value, -2), 16, 10) % $table_num, $step, "0", STR_PAD_LEFT);
        return $item_value;
    }

    /**
     * 获取表面
     *
     * @param mixed $table_name 表名
     * @param mixed $item_value 唯一索引
     * @param int $table_num 表数量
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 19:36:00
     */
    public static function get_table_name($table_name, $item_name, $table_num = 100)
    {
        // sha1: 返回一个40字符长度的16进制数字
        $item_value = sha1(strtolower($item_value));
        // base_convert: 进制间转换,下面是把16进制转成10进制,方便做除法运算
        // str_pad: 把字符串填充为指定的长度,下面是在左边加0,共3位
        $step = $table_num > 100 ? 3 : 2;
        $item_value = str_pad(base_convert(substr($item_value, -2), 16, 10) % $table_num, $step, "0", STR_PAD_LEFT);
        return $table_name . "_" . $itme_value;
    }

    /**
     * 获取当前使用内存
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 19:42:37
     */
    public static function memory_get_usage()
    {
        $memory = memory_get_usage();
        return self::format_bytes($memory);
    }

    /**
     * 获得最高使用内存
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 19:44:22
     */
    public static function memory_get_peak_usage()
    {
        $memory = memory_get_peak_usage();
        return self::format_bytes($memory);
    }

    /**
     * 转换大小单位
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 19:45:58
     */
    public static function format_bytes($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     *  获取数组大小
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 19:48:36
     */
    public static function array_size($arr)
    {
        ob_start();
        print_r($arr);
        $mem = ob_get_contents();
        ob_end_clean();
        $mem = preg_replace("/\n +/", "", $mem);
        $mem = strlen($mem);
        return self::format_bytes($mem);
    }

    /**
     * 数字随机数
     *
     * @param int $num
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 20:24:48
     */
    public static function rand_num($num = 7)
    {
        $rand = "";
        for ($i = 0; $i < $num; $i++) {
            $rand .= mt_rand(0, 9);
        }
        return $rand;
    }

    /**
     * 字母数字混合随机数
     *
     * @param int $num
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 20:26:43
     */
    public static function rand_str($num = 10)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $string = "";
        for ($i = 0; $i < $num; $i++) {
            $string .= substr($chars, rand(0, strlen($chars)), 1);
        }
        return $string;
    }

    /**
     * 汉字转拼音
     *
     * @param mixed $str 汉字
     * @param int $ishead
     * @param int $isclose
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 20:30:27
     */
    public static function pinyin($str, $ishead = 0, $isclose = 1)
    {
        // $str = iconv("utf-8", "gbk/ignore", $str);
        $str = mb_convert_encoding($str, "gbk", "utf-8");
        global $pinyins;
        $restr = "";
        $str = trim($str);
        $slen = strlen($str);
        if ($slen < 2) {
            return $str;
        }
        if (count($pinyins) == 0) {
            $fp = fopen(PATH_DATA . '/pinyin.dat', 'r');
            while (!feof($fp)) {
                $line = trim(fgets($fp));
                $pinyins[$line[0] . $line[1]] = substr($line, 3, strlen($line) - 3);
            }
            fclose($fp);
        }
        for ($i = 0; $i < $slen; $i++) {
            if (ord($str[$i]) > 0x80) {
                $c = $str[$i] . $str[$i + 1];
                $i++;
                if (isset($pinyins[$c])) {
                    if ($ishead == 0) {
                        $restr .= $pinyins[$c];
                    } else {
                        $restr .= $pinyins[$c][0];
                    }
                } else {
                    // $restr .= "_";
                }
            } elseif (preg_match("/[a-z0-9]/i", $str[$i])) {
                $restr .= $str[$i];
            } else {
                // $restr .= "_";
            }
        }
        if ($isclose == 0) {
            unset($pinyins);
        }
        return $restr;
    }

    /**
     * 生成字母前缀
     *
     * @param mixed $s0
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 20:39:33
     */
    public static function letter_first($s0)
    {
        $firstchar_ord = ord(strtoupper($s0{0}));
        if (($firstchar_ord >= 65 and $firstchar_ord <= 91) or ($firstchar_ord >= 48 and $firstchar_ord <= 57)) return $s0{0};
        // $s = iconv("utf-8", "gbk//ignore", $s0);
        $s = mb_convert_encoding($s0, "gbk", "utf-8");
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc >= -20319 && $asc <= -20284) return "A";
        if ($asc >= -20283 && $asc <= -19776) return "B";
        if ($asc >= -19775 && $asc <= -19219) return "C";
        if ($asc >= -19218 && $asc <= -18711) return "D";
        if ($asc >= -18710 && $asc <= -18527) return "E";
        if ($asc >= -18526 && $asc <= -18240) return "F";
        if ($asc >= -18239 && $asc <= -17923) return "G";
        if ($asc >= -17922 && $asc <= -17418) return "H";
        if ($asc >= -17417 && $asc <= -16475) return "J";
        if ($asc >= -16474 && $asc <= -16213) return "K";
        if ($asc >= -16212 && $asc <= -15641) return "L";
        if ($asc >= -15640 && $asc <= -15166) return "M";
        if ($asc >= -15165 && $asc <= -14923) return "N";
        if ($asc >= -14922 && $asc <= -14915) return "O";
        if ($asc >= -14914 && $asc <= -14631) return "P";
        if ($asc >= -14630 && $asc <= -14150) return "Q";
        if ($asc >= -14149 && $asc <= -14091) return "R";
        if ($asc >= -14090 && $asc <= -13319) return "S";
        if ($asc >= -13318 && $asc <= -12839) return "T";
        if ($asc >= -12838 && $asc <= -12557) return "V";
        if ($asc >= -12556 && $asc <= -11848) return "X";
        if ($asc >= -11847 && $asc <= -11056) return "Y";
        if ($asc >= -11055 && $asc <= -10247) return "Z";
        return 0;
    }

    /**
     * 获得某天前的时间戳
     *
     * @param mixed $day
     * @return void
     * @author Masterton <zhengcloud@foxamil.com>
     * @time 2018-4-16 20:58:22
     */
    public static function getxtime($day)
    {
        $day = intval($day);
        return mktime(23, 59, 59, date("m"), date("d") - $day, date("y"));
    }

    /**
     * 读文件
     *
     * @param mixed $url
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-16 21:00:23
     */
    public static function get_file($url, $timeout = 10)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_seropt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            $content = curl_exec();
            curl_close($ch);
            if ($content) return $content;
        }
        $ctx = stream_context_create(array('http' => array('timeout' => $timeout)));
        $content = @file_get_contents($url, 0, $ctx);
        if ($content) return $content;
        return false;
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
