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
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-28 10:08:22
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

        // 检查导出 TODO 预留功能 2018-3-29 15:41:12
        // $this->check_export();

        // 检查缓存 TODO 预留功能
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
            if (!Util::is_win()) $header .= "\033[33m";
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
            // $this->do_collect_page();

            // 从服务器列表中删除当前服务器信息
            // $this->del_server_list(self::$serverid);
        }
    }

    /**
     * Parse command 分析命令行命令
     * php yourfile.php start | stop | status | kill
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 09:20:34
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

    /**
     * Signal hander
     *
     * @param int $signal
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 17:25:14
     */
    public function signal_handler($signal)
    {
        switch ($signal) {
            // Stop
            case SIGINT:
                Log::warn("Program stopping...");
                self::$terminate = true;
                break;
            case SIGUSR2:
                echo "show status\n";
                break;

            default:
                # code...
                break;
        }
    }

    /**
     * Install signal handler.
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 17:29:09
     */
    public function install_signal()
    {
        if (function_exists('pcntl_signal')) {
            // stop
            // static 调用
            // pcntl_signal(SIGINT, array(__CLASS__, 'signal_handler'), false);
            pcntl_signal(SIGINT, array(&$this, 'signal_handler'), false);
            // status
            pcntl_signal(SIGUSR2, array(&$this, 'signal_handler'), false);
            // ignore
            pcntl_signal(SIGPIPE, SIG_IGN, false);
        }
    }

    /**
     * Run as deamon mode.
     *
     * @throws Exception
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 17:33:54
     */
    protected static function daemonize()
    {
        if (!self::$daemonize) {
            return;
        }

        // fork前一定要关闭redis
        Queue::clear_link();

        umask(0);
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new Exception("fork fail");
        } elseif ($pid > 0) {
            exit(0);
        }
        if (-1 === posix_setsid()) {
            throw new Exception("setsid fail");
        }
        // Fork again avoid SVR4 system regain the control of terminal.
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new Exception("fork fail");
        } elseif (0 !== $pid) {
            exit(0);
        }
    }

    /**
     * 导出验证
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 15:06:03 // TODO 预留功能
     */
    public function check_export()
    {
        // 如果设置了导出选项
        if (!empty(self::$config['export'])) {
            if (self::$export_type == 'csv') {
                if (empty(self::$export_file)) {
                    Log::error("Export data into CSV files need to Set the file path.");
                    exit;
                }
            } elseif (self::$export_type == 'sql') {
                if (empty(self::$export_file)) {
                    Log::error("Export data into SQL files need to Set the file path.");
                    exit;
                }
            } elseif (self::$export_type == 'db') {
                if (!function_exists('mysqli_connect')) {
                    Log::error("Export data to a database need Mysql support, unable to load mysqli extension.");
                    exit;
                }

                if (empty(self::$db_config)) {
                    Log::error("Export data to a database need Mysql support, you have not set a config array for connect.");
                    exit;
                }

                $config = self::$db_config;
                @mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name'], $config['port']);
                if (mysqli_connect_errno()) {
                    Log::error("Export data to a database need Mysql support, " . mysqli_connect_errno());
                    exit;
                }

                db::set_connect('default', $config);
                db::init_mysql();

                if (!db::table_exists(self::$export_table)) {
                    Log::error("Table " . self::$export_table . " does not exist");
                    exit;
                }
            }
        }
    }

    /**
     * 检查缓存
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 15:20:12 // TODO
     */
    public function check_cache()
    {
        if (!self::$use_redis || !self::$save_running_state) {
            return false;
        }

        $keys = Queue::keys("*");
        if (count($keys) != 0) {
            // After this operation, 4,318 kB of additional disk space will be used.
            // Do you want to continue? [Y/n]
            // $msg = "发现Reids中有采集数据,是否继续执行,不继续则清空Redis数据重新采集\n";
            $msg = "Found that the data of Redis, no continue will empty Redis data start again\n";
            $msg .= "Do you want to continue? [Y/n]";
            fwrite(STDOUT, $msg);
            $arg = strtolower(trim(fgets(STDIN)));
            $arg = empty($arg) || !in_array($arg, array('y', 'n')) ? 'y' : $arg;
            if ($arg == 'n') {
                foreach ($keys as $key) {
                    $key = str_replace(self::$queue_config['prefix'] . ":", "", $key);
                    Queue::del($key);
                }
            }
        }
    }

    /**
     * 是否入口页面
     *
     * @param mixed $url
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-29 18:03:13
     */
    public static function is_scan_page($url)
    {
        $parse_url = parse_url($url);
        if (empty($parse_url['host']) || !in_array($parse_url['host'], self::$configs['domains'])) {
            return false;
        }
        return true;
    }

    /**
     * 清掉多任务和分布式
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 09:23:48
     */
    public function init_redis()
    {
        if (!self::$use_redis) {
            return false;
        }

        // 添加当前服务器到服务器列表
        $this->add_server_list(self::$serverid, self::$tasknum);

        // 删除当前服务器的任务状态
        // 对于被强制退出的进程有用
        for ($i = 1; $i <= self::$tasknum; $i++) {
            $this->del_task_status(self::$serverid, $i);
        }
    }

    /**
     * 添加当前服务器信息到服务器列表
     *
     * @param int $serverid 当前服务器id
     * @param int $tasknum
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 09:25:22
     */
    public function add_server_list($serverid, $tasknum)
    {
        if (!self::$use_redis) {
            return false;
        }

        // 更新服务器列表
        $server_list_json = Queue::get("server_list");
        $server_list = array();
        if (!$server_list_json) {
            $server_list[$serverid] = array(
                'serverid' => $serverid,
                'tasknum' => $tasknum,
                'time' => time(),
            );
        } else {
            $server_list = json_decode($server_list_json, true);
            $server_list[$serverid] = array(
                'serverid' => $serverid,
                'tasknum' => $tasknum,
                'time' => time(),
            );
            ksort($server_list);
        }
        Queue::set("server_list", json_encode($server_list));
    }

    /**
     * 删除任务状态
     *
     * @param int $serverid
     * @param int $taskid
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 10:31:53
     */
    public function del_task_status($serverid, $taskid)
    {
        if (!self::$use_redis) {
            return false;
        }
        $key = "server-{$serverid}-task_status-{$taskid}";
        Queue::del($key);
    }

    /**
     * 添加URL到队列
     *
     * @param string $url
     * @param array $options
     * @param bool $allower_repeat
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 10:36:45
     */
    public function add_scan_url($url, $options = array(), $allowed_repeat = true)
    {
        // 投递状态
        $status = false;

        $link = $options;
        $link['url'] = $url;
        $link['url_type'] = 'scan_page';
        $link = $this->link_uncompress($link);

        if ($this->is_list_page($url)) {
            $link['url_type'] = 'list_page';
            $status = $this->queue_lpush($link, $allowed_repeat);
        } elseif ($this->is_content_page($url)) {
            $link['url_type'] = 'content_page';
            $status = $this->queue_lpush($link, $allowed_repeat);
        } else {
            $status = $this->queue_lpush($link, $allowed_repeat);
        }

        if ($status) {
            if ($link['url_type'] == 'scan_page') {
                Log::debug("Find scan page: {$url}");
            } elseif ($link['url_type'] == 'list_page') {
                Log::debug("Find list page: {$url}");
            } elseif ($link['url_type'] == 'content_page') {
                Log::debug("Find content page: {$url}");
            }
        }

        return $status;
    }

    /**
     * 连接对象压缩
     *
     * @param mixed $link
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 16:36:48
     */
    public function link_compress($link)
    {
        if (empty($link['url_type'])) {
            unset($link['url_type']);
        }

        if (empty($link['method']) || strtolower($link['method']) == "get") {
            unset($link['method']);
        }

        if (empty($link['headers'])) {
            unset($link['headers']);
        }

        if (empty($link['params'])) {
            unset($link['params']);
        }

        if (empty($link['context_data'])) {
            unset($link['context_data']);
        }

        if (empty($link['proxy'])) {
            unset($link['proxy']);
        }

        if (empty($link['try_num'])) {
            unset($link['try_num']);
        }

        if (empty($link['max_try'])) {
            unset($link['max_try']);
        }

        if (empty($link['depth'])) {
            unset($link['depth']);
        }
        // $json = json_encode($link);
        // $json = gzdeflate($json);
        return $link;
    }

    /**
     * 连接对象解压缩
     *
     * @param string $link
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 10:46:36
     */
    public function link_uncompress($link)
    {
        $link = array(
            'url' => isset($link['url']) ? $link['url'] : '',
            'url_type' => isset($link['url_type']) ? $link['url_type'] : '',
            'method' => isset($link['method']) ? $link['method'] : '',
            'headers' => isset($link['headers']) ? $link['headers'] : array(),
            'params' => isset($link['params']) ? $link['params'] : array(),
            'content_data' => isset($link['content_data']) ? $link['content_data'] : '',
            'proxy' => isset($link['proxy']) ? $link['proxy'] : self::$configs['proxy'],
            'try_num' => isset($link['try_num']) ? $link['try_num'] : 0,
            'max_try' => isset($link['max_try']) ? $link['max_try'] : self::$configs['max_try'],
            'depth' => isset($link['depth']) ? $link['depth'] : 0,
        );

        return $link;
    }

    /**
     * 是否列表页面
     *
     * @param mixed $url
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 10:53:41
     */
    public function is_list_page($url)
    {
        $result = false;
        if (!empty(self::$configs['list_url_regexes'])) {
            foreach (self::$configs['list_url_regexes'] as $regex) {
                if (preg_match("#{$regex}#i", $url)) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * 是否内容页面
     *
     * @param mixed $url
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 10:57:35
     */
    public function is_content_page($url)
    {
        $result = false;
        if (!empty(self::$configs['content_url_regexes'])) {
            foreach (self::$configs['content_url_regexes'] as $regex) {
                if (preg_match("#{$regex}#i", $url)) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * 从队列左边插入
     *
     * @param array $link
     * @param bool $allowed_repeat
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 11:09:24
     */
    public function queue_lpush($link = array(), $allowed_repeat = false)
    {
        if (empty($link) || empty($link['url'])) {
            return false;
        }

        $url = $link['url'];
        $link = $this->link_compress($link);

        $status = false;
        if (self::$use_redis) {
            $key = "collect_urls-" . md5($url);
            $lock = "lock-" . $key;
            // 加锁: 一个进程一个进程轮流处理
            if (Queue::lock($lock)) {
                $exists = Queue::exists($key);
                // 不存在或者当然URL可重复入
                if (!$exists || $allowed_repeat) {
                    // 待爬取网页记录数加一
                    Queue::incr("collect_url_num");
                    // 先标记为待爬取网页
                    Queue::set($key, time());
                    // 入队列
                    $link = json_encode($link);
                    Queue::lpush("collect_queue", $link);
                    $status = true;
                }
                // 解锁
                Queue::unlock($lock);
            }
        } else {
            $key = md5($url);
            if (!array_key_exists($key, self::$collect_urls)) {
                self::$collect_urls_num++;
                self::$collect_urls[$key] = time();
                array_push(self::$collect_queue, $link);
                $status = true;
            }
        }
        return $status;
    }

    /**
     * 从队列右边插入
     *
     * @param array $link
     * @param bool $allowed_repeat
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 13:37:13
     */
    public function queue_rpush($link = array(), $allowed_repeat = false)
    {
        if (empty($link) || empty($link['url'])) {
            return false;
        }

        $url = $link['url'];
        $status = false;
        if (self::$use_redis) {
            $key = "collect_urls-" . md5($url);
            $lock = "lock-" . $key;
            // 加锁: 一个进程一个进程轮流处理
            if (Queue::lock($lock)) {
                $exists = Queue::exists($key);
                // 不存在或则当然URL可重复入
                if (!$exists || $allowed_repeat) {
                    // 待爬取网页记录数加一
                    Queue::incr("collect_urls_num");
                    // 先标记为待爬取网页
                    Queue::set($key, time());
                    // 入队列
                    $link = json_encode($link);
                    Queue::rpush("collect_queue", $link);
                    $status = true;
                }
                // 解锁
                Queue::unlock($lock);
            }
        } else {
            $key = md5($url);
            if (!array_key_exists($key, self::$collect_urls)) {
                self::$collect_urls_num++;
                self::$collect_urls[$key] = time();
                array_unshift(self::$collect_queue, $link);
                $status = true;
            }
        }
        return $status;
    }

    /**
     * 从队列左边取出
     * 后进先出
     * 可以避免采集内容页有分页的时候采集失败数据拼凑不全
     * 还可以按顺序采集列表页
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 13:48:09
     */
    public function queue_lpop()
    {
        if (self::$use_redis) {
            $link = Queue::lpop("collect_queue");
            $link = json_decode($link, true);
        } else {
            $link = array_pop(self::$collect_queue);
        }
        return $link;
    }

    /**
     * 从队列右边取出
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 13:50:46
     */
    public function queue_rpop()
    {
        if (self::$use_redis) {
            $link = Queue::rpop("collect_queue");
            $link = json_decode($link, true);
        } else {
            $link = array_shift(self::$collect_queue);
        }
        return $link;
    }

    /**
     * 队列长度
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 13:53:26
     */
    public function queue_lsize()
    {
        if (self::$use_redis) {
            $lsize = Queue::lsize("collect_queue");
        } else {
            $lsize = count(self::$collect_queue);
        }
        return $lsize;
    }

    /**
     * 采集深度加一
     *
     * @param int $depth 采集深度
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 13:57:59
     */
    public function incr_depth_num($depth)
    {
        if (self::$use_redis) {
            $lock = "lock-depth_num";
            // 锁2秒
            if (Queue::lock($lock, time(), 2)) {
                if (Queue::get("depth_num") < $depth) {
                    Queue::set("depth_num", $depth);
                }
                Queue::unlock($lock);
            }
        } else {
            if (self::$depth_num < $depth) {
                self::$depth_num = $depth;
            }
        }
    }

    /**
     * 获取采集深度
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-3-30 14:03:18
     */
    public function get_depth_num()
    {
        if (self::$use_redis) {
            $depth_num = Queue::get("depth_num");
            return $depth_num ? $depth_num : 0;
        } else {
            return self::$depth_num;
        }
    }

    /**
     * 开始采集
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-1 20:26:59
     */
    public function do_collect_page()
    {
        while ($queue_lsize = $this->queue_lsize()) {
            // 如果是主任务
            if (self::$taskmaster) {
                // 多任务下主任务为准备就绪
                if (self::$tasknum > 1 && !self::$fork_task_complete) {
                    // 主进程采集到两倍于任务数是,生成子任务一起采集
                    if ($queue_lsize > self::$tasknum*2) {
                        self::$fork_task_complete = true;

                        // fork 子进程前一定要先干掉redis连接fd,不然会存在进程互抢redis fd 问题
                        Queue::clear_link();
                        // task进程从2开始,1被master进程所使用
                        for ($i = 2; $i <= self::$tasknum; $i++) {
                            $this->fork_one_task($i);
                        }
                    }
                }

                // 抓取页面
                $this->collect_page();
                // 保存任务状态
                $this->set_task_status();

                // 每采集成功一次页面,就刷新一次面板
                if (!Log::$log_show && !self::$daemonize) {
                    $this->display_ui();
                }
            } else { // 如果是子任务
                // 如果队列中的网页比任务数2倍多,子任务可以采集,否则等待...
                if (!$queue_lsize > self::$tasknum*2) {
                    // 抓取页面
                    $this->collecet_page();
                    // 保存任务状态
                    $this->set_task_status();
                } else {
                    Log::warn("Task(" . self::$taskid . ") waiting...");
                    sleep(1);
                }
            }

            // 检查进程是否收到关闭信号
            $this->check_terminate();
        }
    }

    /**
     * 爬取网页
     *
     * @param mixed $collect_url 要抓取的链接
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-1 20:41:11
     */
    public function collect_page()
    {
        $get_collect_url_num = $this->get_collect_url_num();
        Log::info("Find pages: {$get_collect_url_num}");

        $queue_lsize = $this->queue_lsize();
        Log::info("Waiting for collect pages: {$queue_lsize}");

        $get_collected_url_num = $this->get_collected_url_num();
        Log::info("Collected pages: {$get_collected_url_num}");

        // 多任务的时候输出爬虫序号
        if (self::$task_num > 1) {
            Log::info("Current task id: " . self::$taskid);
        }

        // 先进先出
        $link = $this->queue_rpop();
        $link = $this->link_uncompress($link);
        $url = $links['url'];

        // 标记为已爬取网页
        $this->incr_collected_url_num();

        // 爬取网页开始时间
        $page_time_start = microtime(true);

        Requests::$input_encoding = null;
        $html = $this->request_url($url, $link);

        if (!$html) {
            return false;
        }
        // 当前正在爬取的网页页面的对象
        $page = array(
            'url' => $url,
            'raw' => $html,
            'request' => array(
                'url' => $url,
                'method' => $link['method'],
                'headers' => $link['headers'],
                'params' => $link['params'],
                'context_data' => $link['context_data'],
                'try_num' => $link['try_num'],
                'max_try' => $link['max_try'],
                'depth' => $link['depth'],
                'taskid' => self::$taskid,
            ),
        );
        // printf("memory usage: %.2f M\n", memory_get_usage() / 1024 / 1024);
        unset($html);
        // --------------------------------------
        // 处理回调函数
        // --------------------------------------

        // 判断当前网页是否被反爬虫了,需要开发者实现
        if ($this->is_anti_spider) {
            $is_anti_spider = call_user_func($this->is_anti_spider, $page['raw'], $this);
            // 如果在回调函数里面判断被反爬虫并且返回true
            if ($is_anti_spider) {
                return false;
            }
        }

        // 在一个网页下载完成之后调用,主要用来对下载的网页进行处理
        // 比如下载了某个网页,希望向网页的body中添加html标签
        if ($this->on_download_page) {
            $return = call_user_func($this->on_download_page, $page, $this);
            // 针对那些老是忘记return的人
            if (isset($return)) $page = $return;
        }

        // 是否从当前页面分析提取URL
        // 回调函数如果返回false标识不需要再从此网页中发现待爬url
        $is_find_url = false;
        if ($link['url_type'] == 'scan_page') {
            if ($this->on_scan_page) {
                $return = call_user_func($this->on_scan_page, $page, $page['raw'], $this);
                if (isset($return)) $is_find_url = $return;
            }
        } elseif ($link['url_type'] == 'list_page') {
            if ($this->on_list_page) {
                $return = call_user_func($this->on_list_page, $page, $page['raw'], $this);
                if (isset($return)) $is_find_url = $return;
            }
        } elseif ($link['url_type'] == 'content_page') {
            if ($this->on_content_page) {
                $return = call_user_func($this->on_content_page, $page, $page['raw'], $this);
                if (isset($return)) $is_find_url = $return;
            }
        }

        // on_scan_page、on_list_page、on_content_page
        // 返回false标识不需要再从此网页中发现待爬url
        if ($is_find_url) {
            // 如果深度没有超过最大深度,获取下一级url
            if (self::$configs['max_depth'] == 0 || $link['depth'] < self::$configs['max_depth']) {
                // 分析提取HTML页面中的URL
                $this->get_url($page['raw'], $url, $link['depth'] + 1);
            }
        }

        // 如果是内容页,分析提取HTML页面中的字段
        // 列表页页可以提取数据的,source_type: urlcontext,未实现
        if ($link['url_type'] == 'content_page') {
            $this->get_html_fields($page['raw'], $url, $page);
        }

        // 如果当前深度大于缓存的,更新缓存
        $this->incr_depth_num($link['depth']);

        // 处理页面耗时时间
        $time_run = round(microtime() - $page_time_start, 3);
        Log::debug("Success process page {$url} in {$time_run} s");

        $spider_time_run = Util::time2second(intval(microtime(true) - self::$time_start));
        Log::info("Spider running in {$spider_time_run}");

        // 爬虫爬取每个网页的时间间隔,单位: 毫秒
        if (!isset(self::$configs['interval'])) {
            // 默认睡眠100毫秒,太快了会被认为是ddos
            self::$configs['interval'] = 100;
        }
        usleep(self::$configs['interval'] * 1000);
    }

    /**
     * 替换shell输出内容
     *
     * @param mixed $message
     * @param mixed $force_clear_lines
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-1 21:19:28
     */
    public function replace_echo($message, $force_clear_lines = NULL)
    {
        static $last_lines = 0;

        if (!is_null($force_clear_lines)) {
            $last_lines = $force_clear_lines;
        }

        // 获取终端宽度
        $toss = $status = null;
        $term_width = exce('tput cols', $toss, $status);
        if ($status || empty($term_width)) {
            $term_width = 64; // Arbitrary fall-back term width
        }

        $line_count = 0;
        foreach (explode("\n", $message) as $line) {
            $line_count += count(str_split($line, $term_width));
        }

        // Erasure MAGIC: Clear as many lines as the last output had
        for ($i = 0; $i < $last_lines; $i++) {
            // Return to the beginning of the line
            echo "\r";
            // Erase to the end of the line
            echo "\033[K";
            // Move cursor Up a line
            echo "\033[1A";
            // Return to the beginning of line
            echo "\r";
            // Erase to the end of the line
            echo "\033[K";
            // Return to the beginning of the line
            echo "\r";
            // Can be consolodated into
            // echo "\r\033[K\033[1A\r\033[K\r";
        }

        $last_lines = $line_count;

        echo $message . "\n";
    }

    /**
     * 展示启动界面,Window 不会到这里来
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-1 21:33:07
     */
    public function display_ui()
    {
        $loadavg = sys_getloadavg();
        foreach ($loadavg as $k => $v) {
            $loadavg[$k] = round($v, 2);
        }
        $display_str = "\033[1A\n\033[K-----------------------------\033[47;30m PHPSPIDER \033[0m-----------------------------\n\033[0m";
        // $display_str = "-----------------------------\033[47;30m PHPSPIDER \033[0m-----------------------------\n\033[0m";
        $run_time_str = Util::time2second(time() - self::$time_start, false);
        $display_str .= "PHPSpider version:" . self::VERSION . "          PHP version:" . PHP_VERSION . "\n";
        $display_str .= "start time: " . date("Y-m-d H:i:s", self::$time_start) . " run " . $run_time_str . "\n";

        $display_str .= "spider name: " . self::$configs['name'] . "\n";
        if (self::$multiserver) {
            $display_str .= "server id: " . self::$serverid . "\n";
        }
        $display_str .= "task number: " . self::$tasknum . "\n";
        $display_str .= "load average: " . implode(", ", $loadavg) . "\n";
        $display_str .= "document: https:doc.phpspider.org\n";

        $display_str .= $this->display_task_ui();

        if (self::$multiserver) {
            $display_str .= $this->display_server_ui();
        }

        $display_str .= $this->$display_collect_ui();

        // 清屏
        // $this->clear_echo();
        // 返回到第一行,第一列
        // echo "\033[0;0H";
        $display_str .= "---------------------------------------------------------------------\n";
        $display_str .= "Press Ctrl-C to quir. Start Success.";
        if (self::$terminate) {
            $display_str .= "\n\033[33mWait for the process exits...\033[0m";
        }
        // echo $display_str;
        $this->replace_echo($display_str);
    }

    /**
     * 展示任务界面
     *
     * @return void
     * @author Masterton <zhengcloud@foxmial.com>
     * @time 2018-4-2 11:42:42
     */
    public function display_task_ui()
    {
        $display_str = "-------------------------------\033[47;30m TASKS \033[0m-------------------------------\n";

        $display_str .= "\033[47;30mtaskid\033[0m" . 
            str_pad('', self::$taskid_length+2-strlen('taskid')) . 
            "\033[47;30mtaskid\033[0m" . 
            str_pad('', self::$pid_length+2-strlen('taskpid')) . 
            "\033[47;30mmem\033[0m" . 
            str_pad('', self::$mem_length+2-strlen('mem')) . 
            "\033[47;30mcollect succ\033[0m" . 
            str_pad('', self::$urls_length-strlen('collect succ')) . 
            "\033[47;30mcollect fail\033[0m" . 
            str_pad('', self::$urls_length-strlen('collect fail')) . 
            "\033[47;30mspeed\033[0m" . 
            str_pad('', self::$speed_length+2-strlen('speed')) . 
            "\n";

        // "\033[32;40m [OK] \033[0m";
        $task_status = $this->get_task_status_list(self::$serverid, self::$tasknum);
        foreach ($task_status as $json) {
            $task = json_decode($json, true);
            if (empty($task)) {
                continue;
            }
            $display_str .= str_pad($task['id'], self::$taskid_length+2) . str_pad($task['pid'], self::$pid_length+2) . str_pad($task['mem'] . "MB", self::$mem_length+2) . str_pad($task['collect_succ'], self::$urls_length) . str_pad($task['collect_fail'], self::$urls_length) . str_pad($task['speed'] . "/s", self::$speed_length+2) . "\n";
        }
        // echo "\033[9;0H";
        return $display_str;
    }

    /**
     * 显示服务器界面
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-2 11:57:27
     */
    public function display_server_ui()
    {
        $display_str = "-------------------------------\033[47;30m SERVER \033[0m------------------------------\n";

        $display_str .= "\033[47;30mserver\033[0m" . 
            str_pad('', self::$server_length+2-strlen('serverid')) . 
            "\033[47;30mtasknum\033[0m" . 
            str_pad('', self::$tasknum_length+2-strlen('tasknum')) . 
            "\033[47;30mmem\033[0m" . 
            str_pad('', self::$mem_length+2-strlen('mem')) . 
            "\033[47;30mcollect succ\033[0m" . 
            str_pad('', self::$urls_length-strlen('collect succ')) . 
            "\033[47;30mcollect fail\033[0m" . 
            str_pad('', self::$urls_length-strlen('collect fail')) . 
            "\033[47;30mspeed\033[0m" . 
            str_pad('', self::$speed_length+2-strlen('speed')) . 
            "\n";

        $server_list_json = Queue::get("server_list");
        $server_list = json_decode($server_list_json, true);
        foreach ($server_list as $server) {
            $serverid = $server['serverid'];
            $tasknum = $server['tasknum'];
            $mem = 0;
            $speed = 0;
            $collect_succ = $collect_fail = 0;
            $task_status = $this->get_task_status_list($serverid, $tasknum);
            foreach ($task_status as $json) {
                $task = json_decode($json, true);
                if (empty($task)) {
                    continue;
                }
                $mem += $task['mem'];
                $speed += $task['speed'];
                $collect_fail += $task['collect_fail'];
                $collect_succ += $task['collect_succ'];
            }

            $display_str .= str_pad($serverid, self::$server_length) . 
                str_pad($tasknum, self::$tasknum_length+2) . 
                str_pad($mem . "MB", self::$mem_length+2) . 
                str_pad($collect_succ, self::$urls_length) . 
                str_pad($collect_fail, self::$urls_length) . 
                str_pad($speed . "/s", self::$speed_length+2) . 
                "\n";
        }
        return $display_str;
    }

    /**
     * 显示抓取界面
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-2 13:40:24
     */
    public function display_collect_ui()
    {
        $display_str = "---------------------------\033[47;30m COLLECT STATUS \033[0m--------------------------\n";

        $display_str .= "\033[47;30mfind pages\033[0m" . str_pad('', 16-strlen('find pages')) . 
            "\033[47;30mqueue\033[0m" . str_pad('', 14-strlen('queue')) . 
            "\033[47;30mcollected\033[0m" . str_pad('', 15-strlen('collected')) . 
            "\033[47;30mfields\033[0m" . str_pad('', 15-strlen('fields')) . 
            "\033[47;30mdepth\033[0m" . str_pad('', 12-strlen('depth')) . 
            "\n";

        $collect   = $this->get_collect_url_num();
        $collected = $this->get_collected_url_num();
        $queue     = $this->queue_lsize();
        $fields    = $this->get_fields_num();
        $depth     = $this->get_depth_num();
        $display_str .= str_pad($collect, 16);
        $display_str .= str_pad($queue, 14);
        $display_str .= str_pad($collected, 15);
        $display_str .= str_pad($fields, 15);
        $display_str .= str_pad($depth, 12);
        $display_str .= "\n";
        return $display_str;
    }
}
