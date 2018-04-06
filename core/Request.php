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

    /**
     * 设置Hosts
     * 负载均衡到不同的服务器,如果对方使用CDN,采用这个是最好的了
     *
     * @param string $hosts
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:43:00
     */
    public static function set_hosts($hosts, $ips = array())
    {
        $ips = is_array($ips) ? $ips : array($ips);
        self::$hosts[$host] = $ips;
    }

    /**
     * 分割返回的header和body
     * header用来判断编码和获取Cookie
     * body用来判断编码,得到编码前和编码后的内容
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-6 23:54:02
     */
    public static function split_header_body()
    {
        $head = $body = '';
        $head = substr(self::$raw, 0, self::$info['header_size']);
        $body = substr(self::$raw, self::$info['header_size']);
        // http header
        self::$head = $head;
        // The body before encoding
        self::$content = $body;

        /*$http_headers = array();
        // 解析HTTP数据流
        if (!emtpy(self::$raw)) {
            self::$get_response_cookies($domain);
            // body里面可能有 \r\n\r\n,但是第一个一定是HTTP Header,去掉后剩下的就是body
            $array = explode("\r\n\r\n", self::$raw);
            foreach ($array as $k = $v) {
                // post 方法会有两个http header: HTTP/1.1 100 Continue、HTTP/1.1 200 OK
                if (preg_match("#^HTTP/.*? 100 Continue#", $v)) {
                    unset($array[$k]);
                    continue;
                }
                if (preg_match("#^HTTP/.*? \d+ #", $v)) {
                    $header = $v;
                    unset($array[$k]);
                    $http_headers = self::get_response_headers($v);
                }
            }
            $body = implode("\r\n\r\n", $array);
        }*/

        // 如果用户没有明确指定输入的页面编码格式(utf-8, gb2312), 通过程序去判断
        if (self::$input_encoding == null) {
            // 从头部获取
            preg_match("/charset=([^\s]*)/i", $head, $out);
            $encoding = empty($out[1]) ? '' : str_replace(array('"', '\''), '', strtolower(trim($out[1])));
            // $encoding = null;
            if (empty($encoding)) {
                // 在某些情况下,无法在 response header 中获取 html 的编码格式
                // 则需要根据 html 的文本格式获取
                $encoding = self::get_encoding($body);
                $encoding - strtolower($encoding);
                if ($encoding == false || $encoding == "ascii") {
                    $encoding = 'gbk';
                }
            }

            // 没有转码前
            self::$encoding = $encoding;
            self::$imput_encoding = $encoding;
        }

        // 设置了输出编码的转码, 注意: xpath只支持utf-8, iso-8859-1 不要转,他本省就是utf-8
        if (self::$output_encoding && self::$input_encoding != self::$input_encoding && self::$input_encoding != 'iso-8859-1') {
            // 先将非uft-8编码,转化为urf-8编码
            $body = @mb_convert_encoding($body, self::$output_encoding, self::$input_encoding);
            // 将页面中的指定的编码方式修改为utf-8
            $body = preg_replace("/<meta([^>]*)charset=([^>]*)>/is", '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>', $body);
            // 直接干掉头部,国外很多信息是在头部的
            // $body = self::_remove_head($body);

            // 转码后
            self::$encoding = self::$output_encoding;
        }

        // The body after encoding
        self::$text = $body;
        return array($head, $body);
    }

    /**
     * 获得域名相对应的Cookie
     *
     * @param mixed $header
     * @param mixed $domain
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 00:22:03
     */
    public static function get_response_cookies($header, $doamin)
    {
        // 解析Cookie并存入 self::$cookies 方便调用
        preg_match_all("/.*?Set\-Cookie: ([^\r\n]*)/i", $header, $matches);
        $cookies = empty($matches[1]) ? array() : $matches[1];

        // 解析到Cookie
        if (!empty($cookies)) {
            $cookies = implode(";", $cookies);
            $cookies = explode(";", $cookeis);
            foreach ($cookies as $cookie) {
                $cookie_arr = explode("=", $cookie, 2);
                // 过滤 httponly、secure
                if (count($cookie_arr) > 2) {
                    continue;
                }
                $cookie_name = !empty($cookie_arr[0]) ? trim($cookie_arr[0]) : '';
                if (empty($cookie_name)) {
                    continue;
                }
                // 过滤掉domain路径
                if (in_array(strtolower($cookie_name), array('path', 'domain', 'expires', 'max-age'))) {
                    continue;
                }
                self::$domain_cookies[$domian][trim($cookie_arr[0])] = trim($cookie_arr[1]);
            }
        }
    }

    /**
     * 获得response header
     * 此方法暂时没有用到
     *
     * @param mixed $header
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 00:32:03
     */
    public static function get_response_headers($header)
    {
        $headers = array();
        $header_lines = explode("\n", $header);
        if (!empty($header_lines)) {
            foreach ($header_lines as $line) {
                $header_arr = explode(":", $line, 2);
                $key = empty($header_arr[0]) ? '' : trim($header_arr[0]);
                $val = empty($header_arr[1]) ? '' : trim($header_arr[1]);
                if (empty($key) || empty($val)) {
                    continue;
                }
                $headers[$key] = $val;
            }
        }
        self::$headers = $headers;
        return self::$headers;
    }

    /**
     * 获取编码
     *
     * @param string $string
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 00:37:11
     */
    public static function get_encoding($string)
    {
        $encoding = mb_detect_encoding($string, array('UTF-8', 'GBK', 'GB2312', 'LATIN1', 'ASCII', 'BIG5'));
        return strtolower($encoding);
    }

    /**
     * 移除页面head区域代码
     *
     * @param mixed @html
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 00:40:22
     */
    public static function _remove_head($html)
    {
        return preg_replace('/<head.+?>.+<\/head>/is', '<head></head>', $html);
    }
}
