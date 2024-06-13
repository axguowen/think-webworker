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
 * 事件管理类
 * @package think
 */
class Event extends \think\Event
{
    /**
     * 默认监听者
     * @var array
     */
    protected $defaultListener;

    /**
     * 默认事件别名
     * @var array
     */
    protected $defaultBind;

    /**
     * 重新初始化默认配置参数
     * @access public
     * @return $this
     */
    public function reinitialize()
    {
        return $this->initListener()->initBind();
    }

	/**
	 * 初始化默认监听者
	 * @access protected
	 * @return $this
	 */
	protected function initListener()
	{
        // 如果未初始化默认监听者
        if(is_null($this->defaultListener)){
            // 初始化默认监听者
            $this->defaultListener = $this->listener;
            // 返回
            return $this;
        }
        // 设置当前监听者为默认监听者
        $this->listener = $this->defaultListener;
        // 返回
        return $this;
    }

	/**
	 * 初始化默认事件别名
	 * @access protected
	 * @return $this
	 */
	protected function initBind()
	{
        // 如果未初始化默认事件别名
        if(is_null($this->defaultBind)){
            // 初始化默认事件别名
            $this->defaultBind = $this->bind;
            // 返回
            return $this;
        }
        // 设置当前事件别名为默认事件别名
        $this->bind = $this->defaultBind;
        // 返回
        return $this;
    }
}
