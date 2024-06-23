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
declare (strict_types = 1);

namespace think\webworker\support;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;

/**
 * App 基础类
 */
class App extends \think\App
{
	/**
     * 默认容器绑定标识
     * @var array
     */
    protected $defaultBind;

	/**
     * 架构方法
     * @access public
     * @param string $rootPath 应用根目录
     */
    public function __construct(string $rootPath = '')
    {
		// 设置新的绑定标识
		$this->bind = array_merge($this->bind, [
			// 设置别名
			'think\App' 		=> App::class,
			'think\Config' 		=> Config::class,
			'think\Cookie' 		=> Cookie::class,
			'think\Event' 		=> Event::class,
			'think\Http' 		=> Http::class,
			'think\Middleware' 	=> Middleware::class,
			// 设置session初始化中间件别名
			'think\middleware\SessionInit' 	=> middleware\SessionInit::class,
		]);

        // 执行父类架构方法
		parent::__construct($rootPath);

		// 重置think\Request容器对象绑定标识
		$this->bind['think\Request'] = Request::class;
	}

	/**
	 * 重新初始化
	 * @access public
	 * @param TcpConnection $connection
	 * @param WorkerRequest $workerRequest
	 * @return $this
	 */
	public function reinitialize(TcpConnection $connection, WorkerRequest $workerRequest)
	{
		// 初始化开始时间和内存占用
        $this->beginTime = microtime(true);
        $this->beginMem  = memory_get_usage();
		
        // 设置应用命名空间
        $this->namespace = 'app';
		// 初始化应用目录
        $this->appPath = $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
		// 初始化应用运行时目录
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;

		// 返回
		return $this->initContainer()->initInstances()->initRequest($connection, $workerRequest);
	}

	/**
	 * 初始化容器
	 * @access protected
	 * @return $this
	 */
	protected function initContainer()
	{
		// 如果未初始化默认容器绑定标识
        if(is_null($this->defaultBind)){
            // 初始化容器绑定标识
            $this->defaultBind = $this->bind;
			// 返回
			return $this;
        }
		// 设置当前容器绑定标识为默认容器绑定标识
		$this->bind = $this->defaultBind;
		// 获取全部绑定的标识最终类名
		$aliases = array_flip($this->bind);
		// 遍历当前容器中的对象实例
        foreach ($this->instances as $name => $instance) {
            // 标识不在默认容器绑定标识中
			if(!isset($aliases[$name])){
			    // 从容器中删除对象实例
				$this->delete($name);
			}
        }
		// 从容器中删除路由对象实例
		$this->delete('route');
		// 从容器中删除cookie对象实例
		$this->delete('cookie');
		// 返回
		return $this;
	}

	/**
	 * 初始化容器中的对象实例
	 * @access protected
	 * @return $this
	 */
	protected function initInstances()
	{
		// 初始化配置实例
		$this->config->reinitialize();
		// 初始化http实例
		$this->http->reinitialize();
		// 初始化中间件实例
		$this->middleware->reinitialize();
		// 初始化事件监听实例
		$this->event->reinitialize();
		// 如果容器中存在Session对象实例
		if($this->exists('session')){
		    // 清空session数据
			$this->session->clear();
		}
		// 初始化数据库查询次数
		$this->db->clearQueryTimes();
		// 返回
		return $this;
	}

	/**
	 * 初始化请求
	 * @access protected
	 * @param TcpConnection $connection
	 * @param WorkerRequest $workerRequest
	 * @return $this
	 */
	protected function initRequest(TcpConnection $connection, WorkerRequest $workerRequest)
	{
		// 如果容器中不存在请求实例
		if(!$this->exists('request')){
		    $this->instance('request', $this->make('request', [], true));
		}
		// 解析worker请求对象实例
		$this->request->reinitialize($this, $connection, $workerRequest);
		// 返回
		return $this;
	}

	/**
	 * 是否运行在命令行下
	 * @return bool
	 */
	public function runningInConsole(): bool
	{
		return false;
	}
}