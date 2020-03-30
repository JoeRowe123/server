<?php
/**
 * 多进程，信号 模拟
 */
class SignalDemo
{
    public $time_start;
    public $pid_childs = [];
    public $pid_childs_kill = [];
    public $config;
    public $master_pid;
    public $master_status = 1;
    public $str = '无';

    public function __construct($config)
    {
        $this->config = $config;
        $this->time_start = date('Y-m-d H:i:s');
    }

    public function run()
    {
        declare(ticks=1); //pcntl_signal_dispatch();
        $this->master_pid = posix_getpid();//主id
        $this->clear();//清屏
        $this->command();//指令
        $this->installSignal();//安装信号

        //获取多进程任务
        $task_info = $this->config['task_info'];
        foreach ($task_info as $info) {
            $this->forkOneTask($info);//开启子进程
        }

        while ($this->master_status) {
            $this->printStr();//输出信息
            //守护子进程，以下是模拟子进程结束
            foreach ($this->pid_childs as $k => $pid) {
                posix_kill($pid, SIGUSR1);
                pcntl_waitpid($pid, $status);
                unset($this->pid_childs[$k]);
                $this->str = '主进程把子进程：' . $pid . '杀掉了';
                $this->printStr();
                sleep(20);
            }
            $this->str = '无';
            sleep(1);
        }
        echo '您按了 ctrl+c，主进程结束~~~';
    }


    //开启一个进程
    public function forkOneTask($info)
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            echo 'error';
            exit;
        }
        if ($pid) {
            $this->pid_childs[] = $pid;
        } else {
            //子进程
            while (true) {
                //做你想做的事。。。。。
                sleep(2);
                if (is_array($this->pid_childs_kill) && in_array(posix_getpid(), $this->pid_childs_kill)) {
                    exit();
                }
            }
        }
    }

    //安装信号
    public function installSignal($handler = 'signalHandler')
    {
        pcntl_signal(SIGINT, array($this, $handler));
        pcntl_signal(SIGHUP, array($this, $handler));
        pcntl_signal(SIGUSR1, array($this, $handler));
    }

    //收到信号回调
    public function signalHandler($signal)
    {
        switch ($signal) {
            case SIGINT:
                if (posix_getpid() == $this->master_pid) {
                    $this->master_status = 0; //设置主进程完毕
                } else {
                    //子进程逻辑
                    $this->pid_childs_kill[] = posix_getpid();
                }
                break;
            case SIGUSR1:
                $this->pid_childs_kill[] = posix_getpid();
                break;
        }
    }

    //运行指令
    public function command()
    {
        // 检查运行命令的参数
        global $argv;
        $start_file = $argv[0];

        // 命令
        $command = isset($argv[1]) ? trim($argv[1]) : 'start';

        // 进程号
        $pid = isset($argv[2]) ? $argv[2] : '';

        // 根据命令做相应处理
        switch ($command) {
            case 'start':
                break;
            case 'stop':
                exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}'", $info);
                if (count($info) <= 1) {
                    echo " [$start_file] not run\n";
                } else {
                    echo "[$start_file] stop success";
                    exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}' |xargs kill -SIGINT", $info);
                }
                exit;
                break;
            case 'stop-pid':
                echo "[$start_file] stop pid {$pid}";
                exec("kill {$pid} -SIGINT");
                exit;
                break;

            case 'kill':
                exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}' |xargs kill -SIGKILL");
                break;
            case 'kill-pid':
                exec("kill {$pid} -SIGKILL");
                exit;
                break;

            case 'status':
                exit(0);
            // 未知命令
            default :
                exit("Usage: php yourfile.php {start|stop|kill}\n");
        }
    }


    //清屏
    public function clear()
    {
        $arr = array(27, 91, 72, 27, 91, 50, 74);
        foreach ($arr as $a) {
            echo chr($a);
        }
        //array_map(create_function('$a', 'print chr($a);'), array(27, 91, 72, 27, 91, 50, 74));
    }

    //系统负载
    public function getSysLoad()
    {
        $loadavg = sys_getloadavg();
        foreach ($loadavg as $k => $v) {
            $loadavg[$k] = round($v, 2);
        }
        return implode(", ", $loadavg);
    }

    //打印到屏幕
    public function printStr()
    {
        $display_str = '';
        $display_str .= "-----------------------<white> PHP多进程与信号模拟操作 </white>-------------------" . PHP_EOL;
        $display_str .= '开始时间:' . $this->time_start . PHP_EOL;
        $display_str .= "现在时间:" . date('Y-m-d H:i:s') . PHP_EOL;
        $display_str .= 'Load average: ' . $this->getSysLoad() . PHP_EOL;
        $display_str .= "PHP version:<purple>" . PHP_VERSION . "</purple>" . PHP_EOL;
        $display_str .= "当前子进程数: <red>" . count($this->pid_childs) . "个，PID:(" . implode(',', $this->pid_childs) . ")</red>" . PHP_EOL;
        $display_str .= "当前主进程PID: <red>" . posix_getpid() . "</red>" . PHP_EOL;
        $display_str .= "通知: <red>" . $this->str . "</red>" . PHP_EOL;
        $display_str .= "-----------------------<green> By:DuZhenxun </green>--------------------------" . PHP_EOL;
        $display_str .= "<yellow>Press Ctrl+C to quit.</yellow>" . PHP_EOL;
        $display_str = $this->clearLine($this->replaceStr($display_str));//替换文字,清屏
        echo $display_str;

    }

    //文字替换
    public function replaceStr($str)
    {
        $line = "\033[1A\n\033[K";
        $white = "\033[47;30m";
        $green = "\033[32;40m";
        $yellow = "\033[33;40m";
        $red = "\033[31;40m";
        $purple = "\033[35;40m";
        $end = "\033[0m";
        $str = str_replace(array('<n>', '<white>', '<green>', '<yellow>', '<red>', '<purple>'), array($line, $white, $green, $yellow, $red, $purple), $str);
        $str = str_replace(array('</n>', '</white>', '</green>', '</yellow>', '</red>', '</purple>'), $end, $str);
        return $str;
    }

    //shell 替换显示
    function clearLine($message, $force_clear_lines = NULL)
    {
        static $last_lines = 0;
        if (!is_null($force_clear_lines)) {
            $last_lines = $force_clear_lines;
        }

        // 获取终端宽度
        $toss = $status = null;
        $term_width = exec('tput cols', $toss, $status);
        if ($status || empty($term_width)) {
            $term_width = 64; // Arbitrary fall-back term width.
        }

        $line_count = 0;
        foreach (explode("\n", $message) as $line) {
            $line_count += count(str_split($line, $term_width));
        }
        // Erasure MAGIC: Clear as many lines as the last output had.
        for ($i = 0; $i < $last_lines; $i++) {
            echo "\r\033[K\033[1A\r\033[K\r";
        }
        $last_lines = $line_count;
        return $message . "\n";
    }
}

$config = [];
$config['task_info'] = [
    ['task_id' => 'a_1', 'info' => 'abcd'],
    ['task_id' => 'a_2', 'info' => '222'],
    ['task_id' => 'a_3', 'info' => '3333'],
    ['task_id' => 'a_4', 'info' => '3333'],
];
$obj = new SignalDemo($config);
$obj->run();