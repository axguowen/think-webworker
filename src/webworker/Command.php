<?php
// +----------------------------------------------------------------------
// | ThinkPHP Webworker [Webworker Extension For ThinkPHP]
// +----------------------------------------------------------------------
// | ThinkPHP Webworker 扩展
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\webworker;

use think\Webworker;
use think\console\Command as Base;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;

class Command extends Base
{
    /**
     * 配置
     * @access protected
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('webworker')
            ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart|reload|status|connections', 'start')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the webworker in daemon mode.')
            ->setDescription('Web Framework Extension For ThinkPHP With Workerman');
    }

    /**
     * 执行命令
     * @access protected
     * @param Input $input 输入
     * @param Output $output 输出
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        // 获取命令行参数
        global $argv;
        // 如果是入口文件是think
        if (isset($argv[0]) && $argv[0] == 'think') {
            // 移除think
            array_shift($argv);
            // 指定启动文件
            array_unshift($argv, dirname(__DIR__) . DIRECTORY_SEPARATOR . 'start.php');
            // 构造新命令
            $command = sprintf('%s %s', PHP_BINARY, implode(' ', $argv));
            // 执行命令
            passthru($command);
            // 返回
            return false;
        }
        // 需要启用的函数
        $enableFunctions = [
            'stream_socket_server',
            'stream_socket_client',
            'pcntl_signal_dispatch',
            'pcntl_signal',
            'pcntl_alarm',
            'pcntl_fork',
            'pcntl_wait',
            'posix_getuid',
            'posix_getpwuid',
            'posix_kill',
            'posix_setsid',
            'posix_getpid',
            'posix_getpwnam',
            'posix_getgrnam',
            'posix_getgid',
            'posix_setgid',
            'posix_initgroups',
            'posix_setuid',
            'posix_isatty',
        ];
        // 当前禁用的函数
        $disableFunctions = explode(',', ini_get('disable_functions'));
        foreach ($enableFunctions as $item) {
            if (in_array($item, $disableFunctions, true)) {
                $output->writeln('<error>function [' . $item . '] not enabled, workerman failed to successfully start.</error>');
                return;
            }
        }
        // 获取参数
        $action = $input->getArgument('action');
        // 如果是linux系统
        if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
                $output->writeln('<error>Invalid argument action:' . $action . ', Expected start|stop|restart|reload|status.</error>');
                return false;
            }
            // 移除命令行参数中的think
            array_shift($argv);
        }
        // windows只支持start方法
        elseif ('start' != $action) {
            $output->writeln('<error>Not Support action:' . $action . ' on Windows.</error>');
            return false;
        }

        // 如果是启动
        if ('start' == $action) {
            $output->writeln('Starting webworker...');
        }

        // 读取配置
        $options = $this->app->config->get('webworker', []);
        // 如果是守护进程模式
        if ($input->hasOption('daemon')) {
            $options['daemonize'] = true;
        }

        // 实例化
        $webworker = $this->app->make(Webworker::class, [$options]);

        if (DIRECTORY_SEPARATOR == '\\') {
            $output->writeln('You can exit with <info>`CTRL-C`</info>');
        }

        // 启动
        $webworker->start();
    }
}