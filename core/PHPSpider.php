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
// PHPSpider核心类文件
//----------------------------------

namespace PHPSpider\core;

require_once __DIR__ . '/constants.php';

use Exception;

// 启动的时候生成data目录
Util::path_exists(PATH_DATA);
Util::path_exists(PATH_DATA."/lock");
Util::path_exists(PATH_DATA."/log");
Util::path_exists(PATH_DATA."/cache");
Util::path_exists(PATH_DATA."/status");

class PHPSpider
{
    /**
     * 版本号
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * 爬虫爬取每个网页的时间间隔,0表示不延时, 单位: 毫秒
     *
     * @var integer
     */
    const INTERVAL = 0;

    /**
     * 爬虫爬取每个网页的超时时间, 单位: 秒
     *
     * @var integer
     */
    const TIMEOUT = 5;

    /**
     * 爬取失败次数, 不想失败重新爬取则设置为0
     *
     * @var integer
     */
    const MAX_TRY = 0;

    /**
     * 爬虫爬取网页所使用的浏览器类型: pc、ios、android
     * 默认类型是PC
     *
     * @var string
     */
    const AGENT_PC = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36";
    const AGENT_IOS = "Mozilla/5.0 (iPhone; CPU iPhone OS 9_3_3 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13G34 Safari/601.1";
    const AGENT_ANDROID = "Mozilla/5.0 (Linux; U; Android 6.0.1;zh_cn; Le X820 Build/FEXCNFN5801507014S) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 Chrome/49.0.0.0 Mobile Safari/537.36 EUI Browser/5.8.015S";

    /**
     * pid文件的路径及名称
     *
     * @var string
     */
    // public static $pid_file = '';

    /**
     * 日志目录, 默认在data根目录下
     *
     * @var mixed
     */
    // public static $log_file = '';

    /**
     * 主任务进程ID
     *
     * @var integer
     */
    // public static $master_pid = 0;

    /**
     * 所有任务进程ID
     *
     * @var integer
     */
    // public static $taskpids = array();

    /**
     * Daemonize Linux守护进程运行命令
     *
     * @var bool
     */
    public static $daemonize = false;

    /**
     * 当前进程是否终止
     *
     * @var bool
     */
    public static $terminate = false;

    /**
     * 是否分布式
     *
     * @var bool
     */
    public static $multiserver = false;

    /**
     * 当前服务器ID
     *
     * @var integer
     */
    public static $serverid = 1;

    /**
     * 主任务进程
     *
     * @var integer
     */
    public static $taskmaster = true;

    /**
     * 当前任务ID
     *
     * @var integer
     */
    public static $taskid = 1;

    /**
     * 当前任务进程ID
     *
     * @var integer
     */
    public static $taskpid = 1;

    /**
     * 并发任务数
     *
     * @var integer
     */
    public static $tasknum = 1;

    /**
     * 生成 TODO
     *
     * @var bool
     */
    public static $fork_task_complete = false;

    /**
     * 是否使用redis
     *
     * @var bool
     */
    public static $use_redis = false;

    /**
     * 是否保存爬虫运行状态
     *
     * @var bool
     */
    public static $save_running_state = false;

    /**
     * 配置信息
     *
     * @var array
     */
    public static $configs = array();

    /**
     * 要抓取的URL队列
     * md5(url) => array(
     *     'url' => '', // 要爬取的URL
     *     'url_type' => '', // 要爬取的URL类型,scan_page、list_page、content_page
     *     'method' =>'get', // 默认为"GET"请求,也支持"POST"请求
     *     'headers' => array(), // 此url的Headers,可以为空
     *     'params' => array(), // 发送请求是需添加的参数,可以为空
     *     'context_data' => '', // 此url附加的数据,可以为空
     *     'proxy' => 'false', // 是否使用代理
     *     'try_num' => 0, // 抓取次数
     *     'max_try' => 0 // 允许抓取失败次数
     * )
     *
     * @var array
     */
    public static $collect_queue = array();

    /**
     * 要抓取的URL数组
     * md5($url) => time()
     *
     * @var array
     */
    public static $collect_urls = array();

    /**
     * 要抓取的URl数量
     *
     * @var intager
     */
    public static $collect_urls_num = 0;

    /**
     * 已经抓取的URL数量
     *
     * @var integer
     */
    public static $collected_urls_num = 0;

    /**
     * 当前进程采集成功数
     *
     * @var integer
     */
    public static $collect_succ = 0;

    /**
     * 当前进程采集失败数
     *
     * @var integer
     */
    public static $collect_fail = 0;

    /**
     * 提取到的字段数
     *
     * @var integer
     */
    public static $fields_num = 0;

    /**
     * 采集深度
     *
     * @var integer
     */
    public static $depth_num = 0;

    /**
     * 爬虫开始时间
     *
     * @var date
     */
    public static $time_start = 0;

    /**
     * 任务状态
     *
     * @var array
     */
    public static $task_status = array();

    /**
     * 导出类型配置
     *
     * @var mixed
     */
    public static $export_type = '';
    public static $export_file = '';
    public static $export_conf = '';
    public static $export_table = '';

    /**
     * 数据库配置
     *
     * @var array
     */
    public static $db_config = array();

    /**
     * 队列配置
     *
     * @var array
     */
    public static $queue_config = array();

    /**
     * 运行面板参数长度
     *
     * @var integer
     */
    public static $server_length = 10;
    public static $tasknum_length = 8;
    public static $taskid_length = 8;
    public static $pid_length = 8;
    public static $mem_length = 8;
    public static $urls_length = 15;
    public static $speed_length = 6;

    /**
     * 爬虫初始化时调用,用来指定一些爬取前的操作
     *
     * @var mixed
     * @access public
     */
    public $on_start = null;

    /**
     * 网页状态码回调
     *
     * @var mixed
     * @access public
     */
    public $on_status_code = null;

    /**
     * 判断当前网页是否被反爬虫,需要开发者实现
     *
     * @var mixed
     * @access public
     */
    public $is_anti_spider = null;

    /**
     * 在一个网页下载完成之后调用,主要用来对下载的网页进行处理
     *
     * @var mixed
     * @access public
     */
    public $on_download_page = null;

    /**
     * 在一个attached_url对应的网页下载完成之后调用.
     * 主要用来对下载的网页进行处理
     *
     * @var mixed
     * @access public
     */
    public $on_download_attached_page = null;

    /**
     * 当前页面出去到URL
     *
     * @var mixed
     * @access public
     */
    public $on_fetch_url = null;

    /**
     * URL属于入口页
     * 在爬取到入口url的内容之后,添加新的url到待爬队列之前调用
     * 主要用来发现新的待爬url,并且能给新发现的url附加数据
     *
     * @var mixed
     * @access public
     */
    public $on_scan_page = null;

    /**
     * URL属于列表页
     * 在爬取到入口url的内容之后,添加新的url到待爬队列之前调用
     * 主要用来发现新的待爬url,并且能给新发现的url附加数据
     *
     * @var mixed
     * @access public
     */
    public $on_list_page = null;

    /**
     * URL属于内容页
     * 在爬取到入口url的内容之后,添加新的url到待爬队列之前调用
     * 主要用来发现新的待爬url,并且能给新发现的url附加数据
     *
     * @var mixed
     * @access public
     */
    public $on_content_page = null;

    /**
     * 在抽取到field内容之后调用,对其中包含的img标签进行回调处理
     *
     * @var mixed
     * @access public
     */
    public $on_handle_img = null;

    /**
     * 当一个field的内容被抽取到后进行的回调,
     * 在此回调中可以对网页中抽取的内容进一步处理
     *
     * @var mixed
     * @access public
     */
    public $on_extract_field = null;

    /**
     * 在一个网页的所有field抽取完成之后,
     * 可能需要对field进一步处理,以发布到自己的网站
     *
     * @var mixed
     * @access public
     */
    public $on_extract_page = null;

    /**
     * 如果抓取的页面是一个附属文件,比如图片、视频、二进制文件、apk、ipad、exe
     * 就不去分析它的内容提取field了,提取field只针对HTML
     *
     * @var mixed
     * @access public
     */
    public $on_attachment_file = null;

    function __construct($configs = array())
    {
        // 作用一：Zend引擎每执行1条低级语句就去执行一次 register_tick_function() 注册的函数。
        // 作用二：每执行一次低级语句会检查一次该进程是否有未处理过的信号
        // 这里使用作用二
        // 产生时钟云,解决php7下面ctrl+c无法停止bug
        declare(ticks = 1);

        // 先打开以显示验证错误内容
        Log::$log_show = true;
        Log::$log_file = isset($configs['log_file']) ? $configs['log_file'] : PATH_DATA.'/phpspider.log';
        Log::$log_type = isset($config['log_type']) ? $configs['log_type'] : false;

        // 彩蛋
        $included_files = get_included_files();
        $content = file_get_contents($included_files[0]);
        if (!preg_match("#/\* Do NOT delete this comment \*/#", $content) || !preg_match("#/\* 不要删除这段注释 \*/#", $content))
        {
            $msg = "Unknown error...";
            Log::error($msg);
            exit;
        }

        $configs['name']       = isset($configs['name'])       ? $configs['name']       : 'phpspider';
        $configs['proxy']      = isset($configs['proxy'])      ? $configs['proxy']      : false;
        $configs['user_agent'] = isset($configs['user_agent']) ? $configs['user_agent'] : self::AGENT_PC;
        $configs['client_ip']  = isset($configs['client_ip'])  ? $configs['client_ip']  : array();
        $configs['interval']   = isset($configs['interval'])   ? $configs['interval']   : self::INTERVAL;
        $configs['timeout']    = isset($configs['timeout'])    ? $configs['timeout']    : self::TIMEOUT;
        $configs['max_try']    = isset($configs['max_try'])    ? $configs['max_try']    : self::MAX_TRY;
        $configs['max_depth']  = isset($configs['max_depth'])  ? $configs['max_depth']  : 0;
        $configs['export']     = isset($configs['export'])     ? $configs['export']     : array();

        // csv、sql、db
        self::$export_type  = isset($configs['export']['type'])  ? $configs['export']['type']  : '';
        self::$export_file  = isset($configs['export']['file'])  ? $configs['export']['file']  : '';
        self::$export_table = isset($configs['export']['table']) ? $configs['export']['table'] : '';
        self::$db_config    = isset($configs['db_config'])       ? $configs['db_config']       : array();
        self::$queue_config = isset($configs['queue_config'])    ? $configs['queue_config']    : array();

        // 是否设置了并发任务数,并且大于1,而且不是windows环境
        if (isset($configs['tasknum']) && $configs['tasknum'] > 1 && !util::is_win())
        {
            self::$tasknum = $configs['tasknum'];
        }

        // 是否设置了保留运行状态
        if (isset($configs['save_running_state']))
        {
            self::$save_running_state = $configs['save_running_state'];
        }

        // 是否分布式
        if (isset($configs['multiserver']))
        {
            self::$multiserver = $configs['multiserver'];
        }

        // 当前服务器ID
        if (isset($configs['serverid']))
        {
            self::$serverid = $configs['serverid'];
        }

        // 不同项目的采集以采集名称作为前缀区分
        if (isset(self::$queue_config['prefix']))
        {
            self::$queue_config['prefix'] = self::$queue_config['prefix'].'-'.md5($configs['name']);
        }

        self::$configs = $configs;
    }

    /**
     * 爬虫开始运行
     *
     */
    public function start()
    {
        // 分析命令行命令
        $this->parse_command();

        // 爬虫开始时间
        self::$time_start = time();
        // 当前任务ID
        self::$taskid = 1;
        // 当前任务进程ID
        self::$taskpid = function_exists('posix_getpid') ? posix_getpid() : 1;
        self::$collect_succ = 0;
        self::$collect_fail = 0;

        // --------------------------------
        // 运行前验证
        // --------------------------------

        // 检查PHP版本
        if (version_compare(PHP_VERSION, '5.3.0', 'lt')) {
            Log::error("PHP 5.3+ is required, currently installed version is: " . phpversion());
            exit;
        }

        // 检查CURL扩展
        if (!function_exists('curl_init')) {
            Log::error("The curl extension was not found");
            exit;
        }

        // 多任务需要 pcntl 扩展支持
        if (self::$tasknum > 1 && !function_exists('pcntl_fork')) {
            Log::error("Multitasking needs pcntl, the pcntl extension was not found");
            exit;
        }

        // 守护进程需要 pcntl 扩展支持
        if (self::$daemonize && !function_exists('pcntl_fork')) {
            Log::error("Daemonize needs pcntl, the pcntl extension was not found");
            exit;
        }

        // 集群、保存运行状态、多任务都需要 Redis 支持
        if (self::$multiserver || self::$save_running_state || self::$tasknum > 1) {
            self::$use_redis = true;

            Queue::set_connect('default', self::$queue_config);
            if (!Queue::init()) {
                if (self::$multiserver) {
                    Log::error("Multiserver needs Redis support, " . Queue::$error);
                    exit;
                }

                if (self::$tasknum > 1) {
                    Log::error("Multitasking needs Redis supports, " . Queue::$error);
                    exit;
                }

                if (self::$save_running_state) {
                    Log::error("Spider kept running state needs Reids support, " . Queue::$error);
                    exit;
                }
            }
        }

        // 检查导出
        $this->check_export();

        // 检查缓存
        $this->check_cache();

        // 检查 scan_urls
        if (empty(self::$configs['scan_urls'])) {
            Log::error("No scan url to start");
            exit;
        }

        foreach (self::$configs['scan_urls'] as $url) {
            // 只检查配置中的入口URL,通过 add_scan_url 添加的不检查了.
            if (!$this->is_scan_page($url)) {
                Log::error("Domain of scan_urls (\"{$url}\") does not match the domain of the domain name");
                exit;
            }
        }

        // windows 下没法显示面板,强制显示日志
        if (Util::is_win()) {
            self::$configs['name'] = iconv("UTF-8", "GB2312//IGNORE", self::$configs['name']);
            Log::$log_show = true;
        } elseif (self::$daemonize) {
            // 守护进程下也显示日志
            Log::$log_show = true;
        } else {
            Log::$log_show = isset(self::$configs['log_show']) ? self::$configs['log_show'] : false;
        }

        if (Log::$log_show) {
            global $argv;
            $start_file = $argv[0];

            $header = "";
            if (!Util::is_win()) {
                $header .= "\033[33m";
            }
            $header .= "\n[ " . self::$configs['name'] . " Spider ] is started...\n\n";
            $header .= "  * PHPSpider Version: " . self::VERSION . "\n";
            $header .= "  * Documentation: https:doc.phpspider.org\n";
            $header .= "  * Task Number: " . self::$tasknum . "\n\n";
            $header .= "Input \"php $start_file stop\" to quit. Start success.\n";
            if (!Util::is_win()) {
                $header .= "\033[0m";
            }
            Log::note($header);

            // 如果是守护进程,恢复日志状态
            /*if (self::$daemonize) {
                Log::$log_show = isset(self::$configs['log_show']) ? self::$configs['log_show'] : false;
            }*/

            // 多任务和分布式都要清掉,当然分布式只清自己的
            $this->init_redis();

            // -----------------------------
            // 生成多任务
            // -----------------------------

            // 添加入口URL到队列
            foreach (self::$configs['scan_urls'] as $url) {
                // false 表示不允许重复
                $this->add_scan_url($url, null, false);
            }

            // 放在这个位置,可以添加入口页面
            if ($this->on_start) {
                call_user_func($this->on_start, $this);
            }

            if (!self::$daemonize) {
                if (!Log::$log_show) {
                    // 第一次先清屏
                    $this->clear_echo();

                    // 先显示一次面板,然后下面再每次采集成功显示一次
                    $this->display_ui();
                }
            } else {
                $this->daemonize();
            }

            // 安装信号
            $this->install_signal();

            // 开始采集
            $this->do_collect_page();

            // 从服务器列表中删除当前服务器信息
            $this->del_server_list(self::$serverid);
        }
    }

    /**
     * Parse command 分析命令行命令
     * php yourfile.php start | stop | status | kill
     *
     * @return void
     */
    public function parse_command()
    {
        // 检查运行命令的参数
        global $argv;
        $start_file = $argv[0];

        // 命令
        $command = isset($argv[1]) ? trim($argv[1]) : 'start';

        // 子命令,目前只支持 -d
        $command2 = isset($argv[2]) ? $argv[2] : '';

        // 根据命令做相应处理
        switch ($command) {
            case 'start':
                if ($command2 === '-d') {
                    self::$daemonize = true;
                }
                break;
            case 'stop':
                exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}'", $info);
                if (count($info) <= 1) {
                    echo "PHPSpider[$start_file] not run\n";
                } else {
                    // echo "PHPSpider[$start_file] is stoping ...\n";
                    echo "PHPSpider[$start_file] stop success";
                    exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}' |xargs kill -SIGINT", $info);
                }
                exit;
                break;
            case 'kill':
                exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}' |xargs kill -SIGKILL");
                break;
            case 'status':
                exit(0);

            default :
                exit("Usage: php youfile.php {start|stop|status|kill}\n");
        }
    }
}
