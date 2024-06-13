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

/**
 * Web应用管理类
 * @package think
 */
class Http extends \think\Http
{
    /**
     * 初始化默认参数
     * @access public
     * @return $this
     */
    public function reinitialize()
    {
        // 初始化应用名称
        $this->name = null;
        // 初始化应用路径
        $this->path = null;
        // 初始化路由路径
        $this->routePath = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR;
        // 初始化是否绑定应用
        $this->isBind = false;
        // 返回
        return $this;
    }
}
