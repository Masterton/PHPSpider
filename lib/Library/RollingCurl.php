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
// PHPSpider Curl操作类
//----------------------------------

namespace PHPSpider\Library;

class RollingCurl
{
    /**
     * @var float
     *
     * 同时运行任务数
     * 例如: 有8个请求,则会被分成两批,第一批5个请求,第二批3个请求
     * 注意: 采集知乎的时候,5个比较稳定,7个以上就开始会超时,多进程就没有这样的问题
     * 因为多进程很少几率会放生并发
     */
    public $window_size = 5;

    /**
     * @var float
     *
     * @Timeout is the timeout used for curl_multi_select.
     */
    private $timeout = 10;

    /**
     * @var string|array
     *
     * 应用在每个请求的回调函数
     */
    public $callback;

    /**
     * @var array
     *
     * 设置默认的请求参数
     */
    protected $options = array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        // 注意: TIMEOUT = CONNECTTIMEOUT + 数据获取时间,所以 TIMEOUT 一定要大于 CONNECTTIMEOUT,否则 CONNECTTIMEOUT 设置了就没有意义
        // "Connection timed out after 30001 milliseconds"
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_RETURNTRANSFER => 1;
        CURLOPT_HEADER => 0,
        // 在多线程处理场景下使用超时选项时,会忽略signals对应的处理函数,但是无耐的是还有下概率的crash情况发生
        CURLOPT_NOSIGNAL => 1,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36",
    );

    /**
     * @var array
     *
     */
    private $headers = array();

    /**
     * @var Request[]
     *
     */
    private $requests = array();

    /**
     * @var RequestMap[]
     *
     * Maps handles to request indexes
     */
    private $requestsMap = array();

    public function __construct()
    {
        // TODO
    }

    /**
     * set timeout
     *
     * @param int $timeout
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-28 19:13:09
     */
    public function set_timeout($timeout)
    {
        $this->options[CURLOPT_TIMEOUT] = $timeout;
    }

    /**
     * set proxy
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-28 19:14:24
     */
    public function set_proxy($proxy)
    {
        $this->options[CURLOPT_PROXY] = $proxy;
    }

    /**
     * set referer
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-28 19:15:28
     */
    public function set_referer($referer)
    {
        $this->options[CURLOPT_REFERER] = $referer;
    }

    /**
     * set user_agent
     *
     * @param string $useragent
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-28 19:17:00
     */
    public function set_useragent($useragent)
    {
        $this->options[CURLOPT_USERAGENT] = $useragent;
    }

    /**
     * set COOKIE
     *
     * @param string $cookie
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-28 19:18:38
     */
    public function set_cookie($cookie)
    {
        $this->options[CURLOPT_COOKIE] = $cookie;
    }

    /**
     * set COOKIE JAR
     *
     * @param string $cookie_jar
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-28 19:20:24
     */
    public function set_cookiejar($cookiejar)
    {
        $this->options[CURLOPT_COOKIEJAR] = $cookiejar;
    }

    /**
     * set COOKIE FILE
     *
     * @param string $cookie_file
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-28 19:22:23
     */
    public function set_cookiefile($cookiefile)
    {
        $this->options[CURLOPT_COOKIEFILE] = $cookiefile;
    }

    /**
     * 获取内容的时候是不是连header也一起获取
     *
     * @param mixed $http_raw
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-30 20:31:00
     */
    public function set_http_raw($http_raw = false)
    {
        $this->options[CURLOPT_HEADER] = $http_raw;
    }

    /**
     * set ip
     *
     * @param string $ip
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-30 20:32:36
     */
    public function set_ip($ip)
    {
        $headers = array(
            'CLIENT-IP' => $ip,
            'X-FORWARDED-FOR' => $ip
        );
        $this->headers = $this->headers + $headers;
    }

    /**
     * set Headers
     *
     * @param string $headers
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-30 20:35:01
     */
    public function set_headers($headers)
    {
        $this->headers = $this->headers + $headers;
    }

    /**
     * set Hosts
     *
     * @param string $hosts
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-30 20:36:43
     */
    public function set_hosts($hosts)
    {
        $headers = array(
            'Hosts' => $hosts,
        );
        $this->headers = $this->headers + $headers;
    }

    /**
     * set Gzip
     *
     * @param string $hosts
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-30 20:38:30
     */
    public function set_gzip($gzip)
    {
        if ($gzip) {
            $this->options[CURLOPT_ENCODING] = 'gzip';
        }
    }

    /**
     * request
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-30 20:39:56
     */
    public function request($url, $method = "GET", $fields = array(), $headers = array(), $options = array())
    {
        $this->requests[] = array(
            'url' => $url,
            'method' => $method,
            'fields' => $fields,
            'headers' => $headers,
            'options' => $options,
        );
        return true;
    }

    /**
     *  get_options
     *
     * @param array $request
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-1 21:28:07
     */
    public function get_options($request)
    {
        $options = $this->options;
        $headers = $this->headers;

        if (ini_get('safe_mod' == 'Off' || !ini_get('safe_mode'))) {
            $options[CURLOPT_FOLLOWLOCATION] = 1;
            $options[CURLOPT_MAXREDIRS] = 5;
        }

        // 如果是 get 方式,直接拼凑一个 url 出来
        if (strtolower($request['method']) == 'get' && !empty($request['fields'])) {
            $url = $request['url'] . "?" . http_build_query($request['fields']);
        }
        // 如果是 post 方式
        if (strtolower($request['method']) == 'post') {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = $request['fields'];
        }

        // append custom options for this specific request
        if ($request['options']) {
            $options = $request['options'] + $options;
        }

        if ($request['headers']) {
            $headers = $request['headers'] + $headers;
        }

        // 随机绑定 hosts,做负载均衡
        /*if (self::$hosts) {
            $parse_url = parse_url($url);
            $host = $parse_url['host'];
            $key = rand(0, count(self::$hosts)-1);
            $ip = self::$hosts[$key];
            $url = str_replace($host, $ip, $url);
            self::$headers = array_merge(array('Host:'.$host), self::$headers);
        }*/

        // header 要这样拼凑
        $headers_tmp = array();
        foreach ($headers as $k => $v) {
            $headers_tmp[] = $k . ":" . $v;
        }
        $headers = $headers_tmp;

        $options[CURLOPT_URL] = $request['url'];
        $options[CURLOPT_HTTPHEADER] = $headers;

        return $options;
    }

    /**
     *  GET 请求
     *
     * @param string $url
     * @param array $headers
     * @param array $options
     * @return bool
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-1 21:40:32
     */
    public function get($url, $fields = array(), $headers = array(), $options = array())
    {
        return $this->request($url, 'get', $fields, $headers, $options);
    }
}
