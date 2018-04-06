<?php
// +----------------------------------------------------------------------
// | PHPSpider [ A PHP Framework For Crawler ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Masterton Zheng <zhengcloud@foxmail.com>
// +----------------------------------------------------------------------

//----------------------------------
// PHPSpider请求类文件
//----------------------------------

namespace PHPSpider\core;

if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '')
    {
        return "@$filename;filename="
            . ($postname ?  : basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}

class Requests
{
    const VERSION = "1.0.0";

    protected static $ch = null;

    /**** Public variables ***/

    /* user definable vars */

    public static $timeout = 5;
    public static $encoding = null;
    public static $input_encoding = null;
    public static $output_encoding = null;
    public static $cookies = array(); // array of cookies to pass
    // $cookies['username'] = "seatle";
    public static $rawheaders = array(); // array of raw headers to send
    public static $domain_cookies = array(); // array of cookies for domain to pass
    public static $hosts = array(); // random host binding for make request faster
    public static $headers = array(); // headers returned form server sent here
    public static $useragents = array("requests/2.0.0"); // random agent we masquerade as
    public static $client_ips = array(); // random ip we masquerade as
    public static $proxies = array(); // random proxy ip
    public static $raw = ""; // head + body content returned form server sent here
    public static $head = ""; //head content
    public static $content = "": // The body before encoding
    public static $text = ""; // The body after encoding
    public static $info = array(); // curl info
    public static $history = 302; // http request status before redirect. ex:30x
    public static $status_code = 0; // http request status
    public static $error = ""; // error messages sent here

    /**
     * set timeout
     * $timeout 为数组是会分别设置connect和read
     *
     * @param init or array $timeout
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-3 22:12:37
     */
    public static function set_timeout($timeout)
    {
        self::$timeout = $timeout;
    }

    /**
     * 设置代理
     * 如果代理有多个,请求时会随机使用
     *
     * @param mixed $proxies
     * array (
     *     'socks5://user1:pass2@host:port',
     *     'socks5://user2:pass2@host:port'
     * )
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-3 22:15:54
     */
    public static function set_proxy($proxy)
    {
        self::$proxies = in_array($proxy) ? $proxy : array($proxy);
    }

    /**
     * 自定义请求头部
     * 请求头内容可以用 Requests::$rawheaders 来获取
     * 比如获取Content-Type: Requests::$rawheaders['Content-Type'];
     *
     * @param string $headers
     * @return void
     * @author Masteront <zhengcloud@foxmail.com>
     * @time 2018-4-3 22:19:23
     */
    public static function set_header($key, $value)
    {
        self::$rawheaders[$key] = $value;
    }

    /**
     * 设置全局COOKIE
     *
     * @param string $cookie
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-3 22:21:01
     */
    public static function set_cookie($key, $value, $domain = '')
    {
        if (empty($key)) {
            return false;
        }
        if (!empty($domain)) {
            self::$domain_cookies[$domain][$key] = $value;
        } else {
            self::$cookie[$key] = $value;
        }
        return true;
    }

    /**
     * 批量设置全局cookie
     *
     * @param mixed $cookie
     * @param string $domain
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:11:55
     */
    public static function set_cookies($cookies, $dimain = '')
    {
        $cookies_arr = explode(";", $cookies);
        if (empty($cookies_arr)) {
            return fales;
        }

        foreach ($cookies_arr as $cookie) {
            $cookie_arr = explode("=", $cookie, 2);
            $key = $cookie_arr[0];
            $value = empty($cookie_arr[1]) ? '' : $cookie_arr[1];

            if (!empty($domain)) {
                self::$domain_cookies[$domain][$key] = $value;
            } else {
                self::$cookies[$key] = $value;
            }
        }
        return true;
    }

    /**
     * 获取单一Cookie
     *
     * @param mixed $name cookie名称
     * @param string $domin 不传则取全局cookie,就是手动set_cookie的cookie
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:18:14
     */
    public static function get_cookie($name, $domain = '')
    {
        if (!empty($domain) && !isset(self::$domain_cookies[$domain])) {
            return '';
        }
        $cookies = empty($domain) ? self::$cokkies : self::$domain_cokkies[$domain];
        return isset($cookies[$name]) ? $cookies[$name] : '';
    }

    /**
     * 获取Cookie数组
     *
     * @param string $domain 不传则取全局cookie,就是手动set_cookie的cookie
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:21:56
     */
    public static function get_cookies($domain = '')
    {
        if (!empty($domain) && !isset(self::$domain_cookies[$domain])) {
            return array();
        }
        return empty($domain) ? self::$cookies : self::$domain_cookies[$domain];
    }

    /**
     * 删除Cookie
     *
     * @param string $domain 不传则删除全局Cookie
     * @return void
     * @author Masterton <zhengcloud@foxmain.com>
     * @time 2018-4-6 23:25:19
     */
    public static function del_cookie($key, $domain = '')
    {
        if (empty($key)) {
            return fales;
        }

        if (!empty($domain) && !isset(self::$domain_cookies[$domain])) {
            return false;
        }

        if (!empty($domain)) {
            if (isset(self::$domain_cookies[$domain][$key])) {
                unset(self::$domain_cookies[$domain][$key]);
            }
        } else {
            if (isset(self::$cookies[$key])) {
                unset(self::$cookies[$key]);
            }
        }
        return true;
    }

    /**
     * 删除Cookie数组
     *
     * @param string $domain 不传则删除全局Cookie
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:29:43
     */
    public static function del_cookies($domain = '')
    {
        if (!empty($domain) && !isset(self::$domain_cookies[$domain])) {
            return false;
        }

        if (empty($domain)) {
            self::$cookies = array();
        } else {
            if (isset(self::$domain_cookies[$domain])) {
                unset(self::$domain_cookies[$domain]);
            }
        }
        return true;
    }

    /**
     * 设置随机的user_agent
     *
     * @param string $useragent
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:33:25
     */
    public static function set_useragent($useragent)
    {
        self::$useragent = is_array($useragent) ? $useragent : array($useragent);
    }

    /**
     * set referer
     *
     * @param string $referer
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:35:45
     */
    public static function set_referer($referer)
    {
        self::$rawheaders['Referer'] = $referer;
    }

    /**
     * 设置伪造IP
     * 传入数组则为随机IP
     *
     * @param string $ip
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:37:59
     */
    public static function set_client_ip($ip)
    {
        self::$client_ips = is_array($ip) ? $ip : array($ip);
    }
}
