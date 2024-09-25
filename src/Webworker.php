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

namespace think;

use think\exception\Handle;
use think\exception\HttpException;
use think\webworker\support\think\App;
use think\webworker\support\workerman\Response;
use Workerman\Worker;
use Workerman\Timer;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Throwable;

class Webworker
{
    /**
     * 配置参数
     * @var array
     */
    protected $options = [
        // 进程名称, 方便status命令中查看统计
        'name' => 'think-webworker',
        // 进程数量
        'count' => 1,
        // 是否以守护进程启动
        'daemonize' => false,
        // 监听地址
		'host' => '0.0.0.0',
		// 监听端口
		'port' => 8989,
		// 静态文件支持
		'static_support' => false,
        // 包大小限制
        'max_package_size' => 10 * 1024 * 1024,
		// 请求一定数量后，退出进程重开，防止内存溢出
		'request_limit' => 3,
		// 内容输出文件路径
		'stdout_file' => '',
        // pid文件路径
        'pid_file' => '',
        // 日志文件路径
        'log_file' => '',
    ];

	/**
	 * Worker实例
	 * @var Worker
	 */
	protected $worker;

	/**
	 * web应用实例
	 * @var App
	 */
	protected $app;

    /**
     * 架构函数
     * @access public
     * @param array $options 配置参数
     * @return void
     */
    public function __construct(array $options = [])
    {
        // 合并配置
        $this->options = array_merge($this->options, $options);
		// 初始化
		$this->init();
    }

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function init()
    {
		// 进程名称为空
		if(empty($this->options['name'])){
            $this->options['name'] = 'think-webworker';
        }

		// 构造新的运行时目录
		$runtimePath = \think\facade\App::getRuntimePath() . $this->options['name'] . DIRECTORY_SEPARATOR;
        // 设置runtime路径
        \think\facade\App::setRuntimePath($runtimePath);

		// 主进程reload
		Worker::$onMasterReload = function () {
			// 清理opcache
            if (function_exists('opcache_get_status')) {
                if ($status = opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };

        // 内容输出文件路径
		if(!empty($this->options['stdout_file'])){
			// 目录不存在则自动创建
			$stdout_dir = dirname($this->options['stdout_file']);
			if (!is_dir($stdout_dir)){
				mkdir($stdout_dir, 0755, true);
			}
			// 指定stdout文件路径
			Worker::$stdoutFile = $this->options['stdout_file'];
		}
        // pid文件路径
		if(empty($this->options['pid_file'])){
			$this->options['pid_file'] = $runtimePath . 'worker' . DIRECTORY_SEPARATOR . $this->options['name'] . '.pid';
		}

		// 目录不存在则自动创建
		$pid_dir = dirname($this->options['pid_file']);
		if (!is_dir($pid_dir)){
			mkdir($pid_dir, 0755, true);
		}
		// 指定pid文件路径
		Worker::$pidFile = $this->options['pid_file'];
		
		// 日志文件路径
		if(empty($this->options['log_file'])){
			$this->options['log_file'] = $runtimePath . 'worker' . DIRECTORY_SEPARATOR . $this->options['name'] . '.log';
		}
		// 目录不存在则自动创建
		$log_dir = dirname($this->options['log_file']);
		if (!is_dir($log_dir)){
			mkdir($log_dir, 0755, true);
		}
		// 指定日志文件路径
		Worker::$logFile = $this->options['log_file'];
		// 设置请求限制
		TcpConnection::$defaultMaxPackageSize = $this->options['max_package_size'];

        // 如果指定以守护进程方式运行
        if (true === $this->options['daemonize']) {
            Worker::$daemonize = true;
        }

		// 实例化worker
		$this->worker = new Worker('http://' . $this->options['host'] . ':' . $this->options['port']);
		// 设置进程名称
        $this->worker->name = $this->options['name'];
        // 设置进程数量
        $this->worker->count = $this->options['count'];
        // 设置进程启动事件回调
        $this->worker->onWorkerStart = [$this, 'onWorkerStart'];
        // 设置接收消息事件回调
        $this->worker->onMessage = [$this, 'onMessage'];
    }

    /**
     * 启动回调
     * @access public
	 * @param Worker $worker
	 * @return void
     */
	public function onWorkerStart(Worker $worker): void
	{
		// 实例化WEB应用容器
		$this->app = new App();
		// 初始化
		$this->app->initialize();

		$this->db_hart();
	}

	/**
	 * 避免数据库连接超时
	 * @access public
	 * @return void
	 */
	public function db_heart(): void
	{
		$db = $this->app->db;
		Timer::add(55, function() use($db) {
			$intances = $db->getInstance();
			if (count($intances) <= 0) {
				return;
			}

			foreach($intances as $connection) {
				try {
					$connection->query('select 1');
				} catch (Throwable $e) {}
			}
		});
	}

	/**
     * 接收请求回调
     * @access public
	 * @param TcpConnection $connection
	 * @param Request $request
	 * @return void
     */
	public function onMessage(TcpConnection $connection, Request $request): void
	{
		// 访问资源文件
		$file = $this->app->getRootPath() . 'public' . $request->uri();
		// 启用静态文件支持且文件存在
		if ($this->options['static_support'] && false !== strpos($file, '.php') && is_file($file)) {
			// 获取if-modified-since头
			$if_modified_since = $request->header('if-modified-since');
			// 检查if-modified-since头判断文件是否修改过
			if (!empty($if_modified_since)) {
				$modified_time = date('D, d M Y H:i:s', filemtime($file)) . ' ' . \date_default_timezone_get();
				// 文件未修改
				if ($modified_time === $if_modified_since) {
					// 则返回304
					$connection->send(new Response(304));
					return;
				}
			}

			// 文件修改过或者没有if-modified-since头则发送文件
			$response = (new Response(200, [
				'Server' => $this->worker->name,
			]))->withFile($file);
			// 发送文件
			$connection->send($response);
			return;
		}

		// 重新初始化
        $this->app->reinitialize($connection, $request);

		try {
			// 逻辑处理 START
			while (ob_get_level() > 1) {
				ob_end_clean();
			}

			ob_start();

			$response = $this->app->http->run($this->app->request);
			$content  = ob_get_clean();

			ob_start();

			$response->send();
			$this->app->http->end($response);

			$content .= ob_get_clean() ?: '';
			// 逻辑处理 END

			$header = [
				'Server' => $this->worker->name,
			];
			foreach ($response->getHeader() as $name => $val) {
				$header[$name] = !is_null($val) ? $val : '';
			}

			$keepAlive = $request->header('connection');
			// 获取cookie
			$cookies = $this->app->cookie->getCookie();
			// 响应
			$response = (new Response($response->getCode(), $header))->withBody($content)->withCookies($cookies);
			// 如果是keep-alive则保持连接
			if (($keepAlive === null && $request->protocolVersion() === '1.1') || strtolower($keepAlive) === 'keep-alive') {
				// 发送响应
				$connection->send($response);
				return;
			}
			// 响应并关闭连接
			$connection->close($response);
		} catch (HttpException | \Exception | \Throwable $e) {
			// 响应头
			$header = [
				'Server' => $this->worker->name,
			];
			// 默认状态码
			$code = 500;
			// 默认响应体
			$body = $e->getMessage();
			// 如果是异常
			if ($e instanceof \Exception) {
				// 异常处理器
				$handler = $this->app->make(Handle::class);
				$handler->report($e);
				// 渲染错误
				$resp = $handler->render($this->app->request, $e);
				$code = $resp->getCode();
				$body = $resp->getContent();
			}
			// 获取cookie
			$cookies = $this->app->cookie->getCookie();
			// 获取响应体
			$response = (new Response($code, $header))->withBody($body)->withCookies($cookies);
			// 响应并关闭连接
			$connection->close($response);
		}
		
		// 请求一定数量后，退出进程重开，防止内存溢出
		static $requestCount;
		if (DIRECTORY_SEPARATOR !== '\\' && $this->options['request_limit'] > 0 && ++$requestCount > $this->options['request_limit']) {
			Worker::stopAll();
		}
	}

    /**
     * 启动
     * @access public
     * @return void
     */
    public function start()
    {
        // 启动
        Worker::runAll();
    }

    /**
     * 停止
     * @access public
     * @return void
     */
    public function stop()
    {
        Worker::stopAll();
    }
}
