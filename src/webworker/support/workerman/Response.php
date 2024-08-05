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

namespace think\webworker\support\workerman;

use Workerman\Protocols\Http\Response as Base;

class Response extends Base
{
	/**
     * 动态设置cookie
     * @access public
	 * @param array $cookies
	 * @param bool $merge
	 * @return $this
     */
	public function withCookies(array $cookies, bool $merge = false)
	{
		// 如果不需要合并
		if (!empty($this->_header['Set-Cookie']) && false === $merge) {
			// 清空设置cookie的响应头
		    $this->_header['Set-Cookie'] = [];
		}

		// 遍历动态设置的cookie
		foreach ($cookies as $key => $value) {
			// 获取cookie的值
			[$value, $expire, $option] = $value;
			// 设置cookie响应头
		    $this->cookie(
                $key,
                $value,
                $expire,
                $option['path'],
                $option['domain'],
                $option['secure'] ? true : false,
                $option['httponly'] ? true : false,
                $option['samesite']
            );
		}
		// 返回
		return $this;
	}
}