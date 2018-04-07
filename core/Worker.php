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
// Worker多进程操作类
//----------------------------------

class Worker
{
    // worker 进程数
    public $count = 0;
    // worker id, worker 进程从1开始, 0被 master 进程所使用
    public $worker_id = 0;
    // worker 进程id
    public $worker_pid = 0;
    // 进程用户
    public $user = '';
    // 进程名
    public $title = '';
    // 每个进程是否只运行一次
    public $run_once = true;
    // 是否输出日志
    public $log_show = false;
    // master 进程启动回调
    public $on_start = false;
    // master 进程停止回调
    public $on_stop = false;
    // worker 进程启动回调
    public $on_worker_start = false;
    // worker 进程停止回调
    public $on_worker_stop = false;
    // master 进程ID
    protected static $_master_pid = 0;
    // worker 进程ID
    protected static $_worker_pids = array();
    // master、worker 进程启动时间
    public $time_start = 0;
    // master、worker 进程运行状态[starting|running|shutdown|reload]
    protected static $_status = "starting";

    public function __construct()
    {
        self::$_master_pid = posix_getpid();
        // 产生时钟云,添加后父进程才可以收到信号
        declare(ticks = 1);
        $this->install_signal();
    }

    /**
     * 安装信号处理函数
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 20:20:46
     */
    protected function install_signal()
    {
        // stop
        pcntl_signal(SIGINT, array($this, 'signal_handler'), false);
        // reload
        pcntl_signal(SIGUSR1, array($this, 'signal_handler'), false);
        // status
        pcntl_signal(SIGUSR2, array($this, 'signal_handler'), false);
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);
        // install signal handler for dead kids
        // pcntl_signal(SIGCHLD, array($this, 'signal_handler'));
    }

    /**
     * 卸载信号处理函数
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 20:26:16
     */
    public function uninstall_signal()
    {
        // uninstall stop signal handler
        pcntl_signal(SIGINT, SIG_IGN, false);
        // uninstall reload signal handler
        pcntl_signal(SIGUSR1, SIG_IGN, false);
        // uninstall status signal handler
        pcntl_signal(SIGUSR2, SIG_IGN, false);
    }

    /**
     * 信号处理函数,会被其他类调用到,所以要设置为public
     *
     * @param int $signal
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 20:31:09
     */
    public function signal_handler($signal)
    {
        switch ($signal) {
            // stop 2
            case SIGINT:
                // master 进程和 worker 进程都会调用
                $this->stop_all();
                break;
            case SIGUSR1:
                echo "reload\n";
                break;
            case SIGUSR2:
                echo "status\n";
                break;

            default:
                # code...
                break;
        }
    }

    /**
     * 运行 worker 实例
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 20:35:08
     */
    public function run()
    {
        $this->time_start = microtime(true);
        $this->worker_id = 0;
        $this->worker_pid = posix_getpid();
        $this->set_process_title($this->title);

        // 这里赋值, worker 进程也会克隆到
        if ($this->log_show) {
            Log::$log_show = true;
        }

        if ($this->on_start) {
            call_user_func($this->on_start, $this);
        }

        // worker 进程从1开始, 0被 master 进程所使用
        for ($i = 1; $i <= $this->count; $i++) {
            $this->fork_one_worker($i);
        }
        $this->monitor_workers();
    }

    /**
     * 获取错误类型对应的意义
     *
     * @param integer $type
     * @return string
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-7 20:41:53
     */
    protected function get_error_type($type)
    {
        switch ($type) {
            case E_ERROR: // 1
                return "E_ERROR";
            case E_WARNING: // 2
                return "E_WARNING";
            case E_PARSE: // 4
                return "E_PARSE";
            case E_NOTICE: // 8
                return "E_NOTICE";
            case E_CORE_ERROR: // 16
                return "E_CORE_ERROR";
            case E_CORE_WARNING: // 32
                return "E_CORE_WARNING";
            case E_COMPILE_ERROR: // 64
                return "E_COMPILE_ERROR";
            case E_COMPILE_WARNING: // 128
                return "E_COMPILE_WARNING";
            case E_USER_ERROR: // 256
                return "E_USER_ERROR";
            case E_USER_WARNING: // 512
                return "E_USER_WARNING";
            case E_USER_NOTICE: // 1024
                return "E_USER_WARNING";
            case E_STRICT: // 2048
                return "E_STRICT";
            case E_RECOVERABLE_ERROR: // 4096
                return "E_RECOVERABLE_ERROR";
            case E_DEPRECATED: // 8192
                return "E_DEPRECATED";
            case E_USER_DEPRECATED: // 16384
                return "E_USER_DEPRECATED";

            default:
                return "";
        }
    }
}
