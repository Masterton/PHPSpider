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
     * 判断服务器是不是windows服务器
     *
     * @return boolean
     */
    public static function is_win()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === "WIN";
    } 
}