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
 * 配置管理类
 * @package think
 */
class Config extends \think\Config
{
    /**
     * 默认配置参数
     * @var array
     */
    protected $defaultConfig;

    /**
     * 重新初始化默认配置参数
     * @access public
     * @return $this
     */
    public function reinitialize()
    {
        // 如果未初始化默认配置参数
        if(is_null($this->defaultConfig)){
            // 设置默认配置
            $this->defaultConfig = $this->config;
            // 返回
            return $this;
        }
        // 设置配置为默认配置参数
        $this->config = $this->defaultConfig;
        // 返回
        return $this;
    }
}
