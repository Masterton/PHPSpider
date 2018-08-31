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
}
