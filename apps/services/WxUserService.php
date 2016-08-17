<?php namespace June\apps\services;

/**
 * 微信公众号用户服务类
 *
 * @author 安佰胜
 */

class WxUserService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxUserService();
		}

		return self::$_inst;
	}
	
	/**
	 * 获得关注者列表
	 *
	 * @param string $next_open_id
	 *
	 * @author 安佰胜
	 */
	public function getFollowersList($next_open_id=null) {
		// 获取access_token，有效期7200秒
		$access_token = WxManagerService::getInstance()->getAccessToken();
	
		$url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=$access_token";
		if (!is_null($next_open_id)) {
			$url .= "&next_openid=$next_open_id";
		}
	
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
	
		$res = curl($options);
	
		$ret = $res;
		if (isset($res['errcode']) && !empty($res['errcode'])) {
			$ret['is_ok'] = false;
		} else {
			$ret['is_ok'] = true;
		}
	
		return $ret;
	}
	
	/**
	 * 获取关注者的用户信息
	 *
	 * @param string $open_id
	 * @return array $user_info
	 *
	 * @author 安佰胜
	 */
	public function getFollowerUserInfo($open_id) {
		// 获取access_token，有效期7200秒
		$access_token = WxManagerService::getInstance()->getAccessToken();
	
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$open_id&lang=zh_CN";
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
	
		$res = curl($options);
	
		$ret = $res;
		if (isset($res['errcode']) && !empty($res['errcode'])) {
			$ret['is_ok'] = false;
		} else {
			$ret['is_ok'] = true;
		}
	
		return $ret;
	}
}
?>