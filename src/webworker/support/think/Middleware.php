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

namespace think\webworker\support\think;

/**
 * 中间件管理类
 * @package think
 */
class Middleware extends \think\Middleware
{
    /**
     * 默认执行队列
     * @var array
     */
    protected $defaultQueue;
    
    /**
     * 初始化默认参数
     * @access public
     * @return $this
     */
    public function reinitialize()
    {
        // 未初始化默认执行队列
        if(is_null($this->defaultQueue)){
            // 初始化默认执行队列
            $this->defaultQueue = $this->queue;
            // 返回
            return $this;
        }
        // 设置当前执行队列为默认执行队列
        $this->queue = $this->defaultQueue;
        // 返回
        return $this;
    }
}
