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

namespace think\webworker\support\middleware;

use Closure;
use think\App;
use think\Request;
use think\Response;
use think\Session;

/**
 * Session初始化
 */
class SessionInit
{

    /** @var App */
    protected $app;

    /** @var Session */
    protected $session;

    public function __construct(App $app, Session $session)
    {
        $this->app     = $app;
        $this->session = $session;
    }

    /**
     * 中间件入口执行方法
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        // 获取SessionId
        $sessionId = $request->sessionId();
        // 获取Session的cookie名称
        $cookieName = $this->session->getName();

        // 不为空
        if ($sessionId) {
            // 设置SessionId
            $this->session->setId($sessionId);
        }

        // 初始化Session
        $this->session->init();

        // 设置Session到Request
        $request->withSession($this->session);

        /** @var Response $response */
        $response = $next($request);

        $response->setSession($this->session);

        $this->app->cookie->set($cookieName, $this->session->getId(), $this->session->getConfig('expire'));

        return $response;
    }

    /**
     * 请求结束调度
     * @access public
     * @param Response $response
     * @return void
     */
    public function end(Response $response)
    {
        // 保存session
        $this->session->save();
    }
}
