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

use think\App;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;

/**
 * 请求管理类
 * @package think
 */
class Request extends \think\Request
{
    /**
     * 获取当前根域名
     * @access public
     * @param App $app
	 * @param TcpConnection $connection
	 * @param WorkerRequest $workerRequest
     * @return void
     */
    public function reinitialize(App $app, TcpConnection $connection, WorkerRequest $workerRequest): void
    {
        global $_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES, $_SERVER;
        // 清除以前的请求数据
        $this->clear();
        // 保存 php://input
        $this->input = $workerRequest->rawBody();
        // 请求头
        $this->header = array_change_key_case($workerRequest->header());
        // SERVER参数
        $this->server = $_SERVER = array_merge($_SERVER, $this->parseServer($app, $connection, $workerRequest));
        // 环境变量
        $this->env = $app->env;
        // GET参数
        $this->get = $_GET = $workerRequest->get();
        // input数据
        $inputData = $this->getInputData($this->input);
        // POST参数
        $_POST = $workerRequest->post();
        $this->post = $_POST ?: $inputData;
        // PUT参数
        $this->put = $inputData;
        // COOKIE参数
        $this->cookie = $_COOKIE = $workerRequest->cookie();
        // REQUEST参数
        $this->request = $_REQUEST = array_merge([], $_GET, $_POST, $_COOKIE);
        // FILE参数
        $this->file = $_FILES = $workerRequest->file();
    }

	/**
	 * 清除请求数据
	 * @access protected
	 * @return void
	 */
	protected function clear()
	{
        $this->method = null;
        $this->domain = null;
        $this->host = null;
        $this->subDomain = null;
        $this->panDomain = null;
        $this->url = null;
        $this->baseUrl = null;
        $this->baseFile = null;
        $this->root = null;
        $this->pathinfo = null;
        $this->path = null;
        $this->realIP = null;
        $this->controller = null;
        $this->action = null;
        $this->param = [];
        $this->rule = null;
        $this->route = [];
        $this->middleware = [];
        $this->session = null;
        $this->content = null;
        $this->filter = null;
        $this->secureKey = null;
        $this->mergeParam = false;
    }

    /**
     * 解析当前SERVER超全局变量
     * @access protected
     * @param App $app
	 * @param TcpConnection $connection
	 * @param WorkerRequest $workerRequest
     * @return string
     */
    protected function parseServer(App $app, TcpConnection $connection, WorkerRequest $workerRequest)
    {
        // 根目录
        $rootPath = $app->getRootPath();
        // 请求path
        $requestPath = $workerRequest->path();
        // 脚本文件
        $scriptFile = $this->parseScriptFile($requestPath);
        // DOCUMENT_ROOT
        $documentRoot = $rootPath . 'public';
        // REQUEST_URI
        $requestUri = $workerRequest->uri();
        // SCRIPT_FILENAME
        $scriptFilename = $documentRoot . DIRECTORY_SEPARATOR . $scriptFile;
        // SCRIPT_NAME
        $scriptName = '/' . $scriptFile;
        // DOCUMENT_URI
        $documentUri = $scriptName;
        // PHP_SELF
        $phpSelf = $scriptName;
        // PATH_INFO
        $pathInfo = '';
        // 如果REQUEST_URI包含脚本文件名
        if(strpos($requestUri, $scriptName) === 0) {
            // 如果REQUEST_URI包含PATHINFO变量名
            if(strpos($requestUri, $scriptName . '?' . $this->varPathinfo . '=') === 0) {
                $pathInfo = explode($scriptName . '?' . $this->varPathinfo . '=', $requestUri)[1] ?: '/';
            }
            // 不包含PATHINFO变量名
            else{
                $pathInfo = explode($scriptName, $requestUri)[1] ?: '/';
                $documentUri = $phpSelf = $requestUri;
            }
        }

        // 获取应用入口文件配置
        $appEntranceFiles = $app->config->get('webworker.app_entrance_files', []);
        // 如果脚本文件不是index.php且存在入口文件配置
        if($scriptFile != 'index.php' && isset($appEntranceFiles[$scriptFile])) {
            // 获取入口文件配置
            $appEntrance = $appEntranceFiles[$scriptFile];
            // 如果是字符串
            if(is_string($appEntrance)) {
                // 设置应用名
                $app->http->name($appEntrance);
            }
            elseif(is_array($appEntrance)) {
                // 如果存在应用名
                if(!empty($appEntrance['name'])){
                    // 设置应用名
                    $app->http->name($appEntrance['name']);
                }
                // 如果存在应用目录
                if(!empty($appEntrance['path'])){
                    // 设置应用名
                    $app->http->path($appEntrance['path']);
                }
            }
        }

        // 返回
        return [
			'SERVER_PROTOCOL'      => 'HTTP/' . $workerRequest->protocolVersion(),
			'SERVER_SOFTWARE'      => 'think-webworker',
			'SERVER_NAME'          => $workerRequest->host(true),
			'HTTP_HOST'            => $workerRequest->host(),
			'HTTP_USER_AGENT'      => $workerRequest->header('user-agent'),
			'HTTP_ACCEPT'          => $workerRequest->header('accept'),
			'HTTP_ACCEPT_LANGUAGE' => $workerRequest->header('accept-language'),
			'HTTP_ACCEPT_ENCODING' => $workerRequest->header('accept-encoding'),
			'HTTP_COOKIE'          => $workerRequest->header('cookie'),
			'HTTP_CONNECTION'      => $workerRequest->header('connection'),
			'CONTENT_TYPE'         => $workerRequest->header('content-type'),
			'REMOTE_ADDR'          => $connection->getRemoteIp(),
			'REMOTE_PORT'          => $connection->getRemotePort(),
			'CONTENT_LENGTH'       => $workerRequest->header('content-length'),
			'REQUEST_TIME'         => time(),
			'QUERY_STRING'         => $workerRequest->queryString(),
			'REQUEST_METHOD'       => $workerRequest->method(),
			'REQUEST_URI'          => $requestUri,
            'DOCUMENT_ROOT'        => $documentRoot,
            'DOCUMENT_URI'         => $documentUri,
            'PHP_SELF'             => $phpSelf,
            'SCRIPT_NAME'          => $scriptName,
            'SCRIPT_FILENAME'      => $scriptFilename,
            'PATH_INFO'            => $pathInfo,
		];
    }

    /**
     * 解析当前脚本文件
     * @access protected
     * @param string $path
     * @return string
     */
    protected function parseScriptFile($path)
    {
        // 清除分隔符
        $path = trim($path, '/');
        // 如果是空或者是index.php
        if(empty($path) || $path == 'index.php') {
            return 'index.php';
        }
        // 目录分隔
        $pathData = explode('/', $path);
        // 获取第一个元素
        $scriptName = array_shift($pathData);
        // 不是PHP文件
        if(!preg_match('/^\w+\.php$/i', $scriptName)) {
            return 'index.php';
        }
        // 返回
        return $scriptName;
    }
}
