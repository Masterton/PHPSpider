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
// PHPSpider日志类文件
//----------------------------------

namespace PHPSpider\core;
// 引入PATH_DATA
require_once __DIR__ . '/constants.php';

class Log
{
    /**
     * 是否显示日志
     *
     * @var bool
     */
    public static $log_show = false;

    /**
     * 日志类型
     *
     * @var mixed
     */
    public static $log_type = flase;

    /**
     * 日志路径
     *
     * @var string
     */
    public static $log_file = "data/phpspider.log";

    /**
     * 设置 linux 命令行显示的字体颜色
     * 格式：echo "\033[33m 中间包围的文字颜色会改变 \033[0m"
     *
     * @var string
     */
    public static $out_sta = "";

    /**
     * 关闭设置的所有属性
     *
     * @var string
     */
    public static $out_end = "";

    /**
     * 提示类型日志
     *
     * @param string $msg
     */
    public static function note($msg)
    {
        self::$out_sta = self::$out_end = "";
        self::msg($msg, 'note');
    }

    /**
     * 普通类型日志
     *
     * @param string $msg
     */
    public static function info($msg)
    {
        self::$out_sta = self::$out_end = "";
        self::msg($msg, 'info');
    }

    /**
     * 警告类型日志
     *
     * @param string $msg
     */
    public static function warn($msg)
    {
        self::$out_sta = self::$out_end = "";
        if (!Util::is_win()) {
            self::$out_sta = "\033[33m";
            self::$out_end = "\033[0m";
        }

        self::msg($msg, 'warn');
    }

    /**
     * 调试类型日志
     *
     * @param string $msg
     */
    public static function debug($msg)
    {
        self::$out_sta = self::$out_end = "";
        if (!Util::is_win()) {
            self::$out_sta = "\033[36m";
            self::$out_end = "\033[0m";
        }

        self::msg($msg, 'debug');
    }

    /**
     * 错误类型日志
     *
     * @param string $msg
     */
    public static function error($msg)
    {
        self::$out_sta = self::$out_end = "";
        if (!Util::is_win()) {
            self::$out_sta = "\033[31m";
            self::$out_end = "\033[0m";
        }

        self::msg($msg, 'error');
    }

    /**
     * 记录日志 XXX
     *
     * @param string $msg
     * @param string $log_type Note|Warning|Error
     * @return void
     */
    public static function msg($msg, $log_type)
    {
        if ($log_type != 'note' && self::$log_type && strpos(self::$log_type, $log_type) === false) {
            return false;
        }

        if ($log_type == 'note') {
            $msg = self::$out_sta . $msg . "\n" . self::$out_end;
        } else {
            $msg = self::$out_sta . date("Y-m-d H:i:s") . " [{$log_type}] " . $msg . self::$out_end . "\n";
        }

        if (self::$log_show) {
            echo $msg;
        }
        file_put_contents(self::$log_file, $msg, FILE_APPEND | LOCK_EX);
    }

    /**
     * 记录日志 XXX
     *
     * @param string $msg
     * @param string $log_type Note|Warning|Error
     * @return void
     */
    public static function add($msg, $log_type = '')
    {
        if ($log_type != '') {
            $msg = date("Y-m-d H:i:s") . " [{$log_type}] " . $msg . "\n";
        }
        if (self::$log_show) {
            echo $msg;
        }
        file_put_contents(PATH_DATA . "/log/error.log", $msg, FILE_APPEND | LOCK_EX);
    }
}
