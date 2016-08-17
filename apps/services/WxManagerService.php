<?php namespace June\apps\services;

use June\Core\JuneException;
/**
 * 微信公众号管理服务类
 *
 * @author 安佰胜
 */
class WxManagerService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxManagerService();
		}

		return self::$_inst;
	}
	
	/**
	 * 验证微信接入合法性
	 * 
	 * @return boolean 接入是否合法
	 * 
	 * @author 安佰胜
	 */
	public function checkSignature() {
		$signature = xg('signature');
		$timestamp = xg('timestamp');
		$nonce = xg('nonce');
		
		$token = C('wx_conf', 'token'); // 微信分配的接入验证令牌
		$tmp_arr = array($token, $timestamp, $nonce);
		sort($tmp_arr, SORT_STRING);
		$tmp_str = implode($tmp_arr);
		$tmp_str = sha1($tmp_str);
		
		if ($tmp_str == $signature) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 获取微信操作授权凭证access_token（目前为7200秒内有效）
	 * access_token的设置可以保护appsecret
	 * 
	 * @return mixed $access_token
	 * 
	 * @author 安佰胜
	 */
	public function getAccessToken() {
		$m_wx_tokens = $this->_db->getMWxTokens();
		
		$criteria = array('type' => 'access_token');
		$ret = $m_wx_tokens->findOne($criteria);
		
		// access令牌记录不存在或者已过期（目前为7200秒），需要重新到微信服务器获取access令牌
		if (empty($ret['token']) || 
			(isset($ret['update_ts']) && (time()-$ret['update_ts']->{'sec'})>$ret['expires_in']-200)) {
					
			$token_result = $this->_refreshAccessToken();
			
			if ($token_result['is_ok']) {
				$data = array('token' => $token_result['access_token'], 
							  'expires_in' => 7200,
							  'ext_info' => array('err_code' => null, 'err_msg' => ''));
			} else {
				$data = array('token' => false, 
							  'expires_in' => null,
						      'ext_info' => array('err_code' => $token_result['err_code'], 'err_msg' => $token_result['err_msg']));
			}
			$data['update_ts'] = new \MongoDate(time());
			$fields = array('token' => true, 'type' => true, 'update_ts' => true);
			$options = array('upsert' => true);
			
			$m_wx_tokens->findAndModify($criteria, array('$set' => $data), $fields, $options);
			$token = $data['token'];
		} else {
			$token = $ret['token'];
		}
		
		return $token;
	}
	
	/**
	 * 刷新获取新的access token
	 * 注意：该接口每天的调用频率限制为2000次！！！
	 * 
	 * @return array $token_result
	 * 
	 * @author 安佰胜
	 */
	private function _refreshAccessToken() {
		$app_id = C('wx_conf', 'app_id');
		$secret = C('wx_conf', 'app_secret');
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$app_id&secret=$secret";
		
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		$result = curl($options);
		
		if (isset($result['access_token'])) {
			$result['is_ok'] = true;
		} else {
			$result['is_ok'] = false;
			$result['err_code'] = $result['errcode'];
			$result['err_msg'] = $result['errmsg'];
		}
		
		return $result;
	}
	
	/**
	 * 获取js_api调用的签名数据包
	 * 
	 * @author 安佰胜
	 */
	public function getJsApiSignPackage() {
		// 获取js接口的调用凭证（刷新频率：7200秒）
		$js_api_ticket = $this->_getJsApiTicket();
		
		// 注意： URL 一定要动态获取，不能硬编码！！！
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		
		$timestamp = time();
		$nonce_str = $this->_createNonceStr();
		
		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$js_api_ticket&noncestr=$nonce_str&timestamp=$timestamp&url=$url";
		
		$signature = sha1($string);
		
		$sign_package = array("appId"     => C('wx_conf', 'app_id'),
							  "nonceStr"  => $nonce_str,
							  "timestamp" => $timestamp,
				              "url"       => $url,
				              "signature" => $signature,
				              "rawString" => $string
		);
		
		return $sign_package;
	}
	
	/**
	 * 获得用于调用微信JS接口的临时票据
	 * 
	 * @author 安佰胜
	 */
	private function _getJsApiTicket() {
		$criteria = array('type' => 'js_api_ticket');
		
		$tk_info = $this->_db->getMWxTokens()->findOne($criteria);
		
		if (empty($tk_info['token']) || 
		    (isset($tk_info['update_ts']) && (time()-$tk_info['update_ts']->{'sec'})>$tk_info['expires_in']-200)) {
			
		    $access_token = $this->getAccessToken();
		    
		    $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=" . $access_token;
		    $options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		    
		    // 从微信服务器获取js接口临时票证（7200秒后失效，需要重新获取）
		    $res = curl($options);
		    
		    if (empty($res['errcode'])) {
		    	$ticket_data = array('type'       => 'js_api_ticket',
		    						 'token'      => $res['ticket'],
		    						 'expires_in' => $res['expires_in'],
		    						 'ext_info'   => array(),
		    						 'create_ts'  => new \MongoDate(time()),
		    						 'update_ts'  => new \MongoDate(time()),
		    	);
		    	
		    	$this->_db->getMWxTokens()->insert($ticket_data);
		    	
		    	$ticket = $res['ticket'];
		    } else {
		    	$ticket = false;
		    }
		} else {
			$ticket = $tk_info['token'];
		}
		
		return $ticket;
	}
	
	/**
	 * 生成一个随机字符串
	 * 
	 * @author 安佰胜
	 */
	private function _createNonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}
	
	/**
	 * 获取微信服务器的ip地址列表
	 * 
	 * @return array $ip_result 形如：{'ip_list':['192.168.1.1']}
	 * 
	 * @author 安佰胜
	 */
	public function getWxIpList() {
		$access_token = $this->getAccessToken();
		
		$url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=$access_token";
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$result = curl($options);
		
		if (isset($result['ip_list'])) {
			$result['is_ok'] = true;
		} else {
			$result['is_ok'] = false;
			$result['err_code'] = $result['errcode'];
			$result['err_msg'] = $result['errmsg'];
		}
		
		return $result;
	}
	
	/**
	 * 创建微信公众号二维码ticket，凭借ticket到指定URL换取二维码图片
	 * 提醒：换取地址为https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=TICKET，TICKET记得进行UrlEncode
	 * 
	 * @param string $type
	 * @param mixed $scene_val 临时二维码时为32位非零整型数；永久二维码时为1-100000
	 * @return array $ticket_result
	 * 
	 * @author 安佰胜
	 */
	public function getQrCodeTicket($type, $scene_val) {
		$access_token = $this->getAccessToken();
		
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
		switch ($type) {
			case 'QR_SCENE':
				// 临时二维码，32位非零整型数，7天失效
				$data = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_val.'}}}';
				break;
			case 'QR_LIMIT_SCENE':
				// 永久二维码，1至100000整型数，永不失效
				$data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$scene_val.'}}}';
				break;
			case 'QR_LIMIT_STR_SCENE':
				// 永久二维码，1至64位字符串，永不失效
				$data = '{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$scene_val.'"}}}';
				break;
			default:
				// 临时二维码，32位非零整型数，7天失效
				$data = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_val.'}}}';
				break;
		}
		
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $data, 'data_type' => 'json');
		
		$result = curl($options);
		
		if (isset($result['ticket'])) {
			// 保存微信二维码的获取凭证ticket
			$ticket_data = $result;
			$ticket_data['expired_ts'] = new \MongoDate(time()+$result['expire_seconds']);
			
			$res = $this->_db->getMWxQrCodeTickets()->insertOneGetId($ticket_data);
			
			$result['is_ok'] = true;
			$result['ticket_id'] = empty($res) ? null : $res;
		} else {
			$result['is_ok'] = false;
			$result['err_code'] = $result['errcode'];
			$result['err_msg'] = $result['errmsg'];
		}
		
		return $result;
	}
	
	/**
	 * 凭借二维码获取凭证获取二维码图片
	 * 
	 * @param string $ticket
	 * @throws \Exception
	 * @return resource $image
	 * 
	 * @author 安佰胜
	 */
	public function getQrCodeImage($ticket) {
		if (empty($ticket)) {
			throw new JuneException('二维码获取凭证不能为空！');
		}
		
		$ticket_info = $this->_db->getMWxQrCodeTickets()->findOne(array('ticket' => $ticket));
		
		if (empty($ticket_info) || $ticket_info['expired_ts']->{'sec'} < (time()-5)) {
			throw new JuneException('二维码获取凭证过期或失效！');
		} else {
			$qr_img_url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . $ticket;
			$options = array('url' => $qr_img_url, 'method' => 'GET', 'data_type' => 'json');
			
			return curl($options);
		}
	}
	
	/**
	 * 设置微信公众号所属行业供模板消息使用（每月只允许修改一次），亦可在MP中设置
	 * 
	 * @param integer $industry_id_1
	 * @param integer $industry_id_2
	 * 
	 * @author 安佰胜
	 */
	public function setIndustry($industry_id_1, $industry_id_2) {
		
	}
	
	/**
	 * 添加消息模板
	 * 
	 * @param string $tpl_id
	 * 
	 * @author 安佰胜
	 */
	public function addMsgTemplate($tpl_id) {
		
	}
	
	/**
	 * 创建微信自定义菜单
	 * 
	 * @param array $menu_data
	 * 
	 * @author 安佰胜
	 */
	public function createMenu($menu_data) {
		$access_token = $this->getAccessToken();
		
		if (empty($menu_data) || !is_array($menu_data)) {
			throw new JuneException("微信菜单内容为空或格式非法！");
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
		$menu_json = json_encode_ex($menu_data);
		error_log($menu_json);
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $menu_json, 'data_type' => 'json');
		
		return curl($options);
	}
	
	/**
	 * 获取当前自定义菜单
	 * 
	 * @author 安佰胜
	 */
	public function getMenu() {
		$access_token = $this->getAccessToken();
		
		$url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=" . $access_token;
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		return curl($options);
	}
	
	/**
	 * 删除自定义菜单
	 * 
	 * @author 安佰胜
	 */
	public function deleteMenu() {
		$access_token = $this->getAccessToken();
		
		$url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=" . $access_token;
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		return curl($options);
	}
	
	/**
	 * 从微信内置浏览器网页上获得用户的授权码，并跳转回至调用页面
	 * 
	 * 提醒：授权作用域取值
	 * 		snsapi_base（不弹授权页面，只获取openid）,
	 * 		snsapi_userinfo（弹授权页面，可通过openid获得昵称、性别等个人信息，即使用户未关注只要有授权即可获取信息）
	 * 
	 * @param string $scope 授权作用域
	 * @return string $code 授权码，只能用一次，如5分钟未使用则自动过期！！！
	 * 
	 * @author 安佰胜
	 */
	public function getOAuthCode($scope) {
		$code = xg('code');
		
		if (empty($code)) {
			$redirect_uri = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
			
			$url = $this->_genUrl4OAuthCode($scope, $redirect_uri);

			header("Location: $url");
			exit;
		} else {
			return $code;
		}
	}
	
	/**
	 * 生成页面授权码获取链接
	 * 
	 * @param string $scope 授权作用域
	 * @param string $redirect_url 回调地址，最好使用https以确保code的安全性
	 * @return string $url
	 * 
	 * @author 安佰胜
	 */
	private function _genUrl4OAuthCode($scope, $redirect_uri) {
		$app_id = C('wx_conf', 'app_id');
		
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?" .
			   "appid=$app_id&redirect_uri=$redirect_uri&response_type=code&" .
			   "scope=$scope&state=STATE#wechat_redirect";
		
		return $url;
	}
	
	/**
	 * 从微信内置浏览器网页上获取用户的open_id
	 * 
	 * @author 安佰胜
	 */
	public function getOAuthOpenId($code) {
		$oauth_token_info = $this->getOAuthAccessToken($code);
		
		// 此处获得的access_token是一个页面授权令牌，与基础支持中的access_token（安全级别更高，只能应用在服务器上）不同
		
	}
	
	public function getOAuthAccessTokenByOpenId() {
		// 如果网页授权access_token未过期，则直接返回
		// 如果网页授权access_token已过期，但refresh_token未过期，则使用refresh_token重新获取access_token
		// 如果refresh_token也已过期，则需要重新获得用户授权code，用code换取网页授权access_token
	}
	
	/**
	 * 检查页面授权access_token是否仍然有效
	 * 
	 * 提醒：验证页面授权access_token接口调用频率微信官方暂无限制（20151104）
	 * 
	 * @param string $access_token
	 * @param string $open_id
	 * @return boolean $is_valid
	 * 
	 * @author 安佰胜
	 */
	public function checkOAuthAccessToken($access_token, $open_id) {
		$url = "https://api.weixin.qq.com/sns/auth?access_token=$access_token&openid=$open_id";
		
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		return $res['errcode'] ? false : true;
	}
	
	/**
	 * 使用refresh_token刷新access_token（用户已授权且授权未过期）
	 * 
	 * 提醒：刷新页面授权access_token接口调用频率微信官方暂无限制（20151104）
	 * 	     
	 * @param string $refresh_token
	 * @return mixed $res
	 *
	 * errcode:40030 - 不合法的refresh_token
	 * errcode:41003 - 缺少refresh_token参数
	 * errcode:42002 - refresh_token超时
	 * 
	 * @author 安佰胜
	 */
	public function getOAuthAccessTokenByRefreshToken($refresh_token) {
		$app_id = C('wx_conf', 'app_id');
		
		$url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=$app_id&grant_type=refresh_token&refresh_token=$refresh_token";
		
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		// 向微信接口刷新页面授权access_token
		$res = curl($options);
		
		$ret = $res;
		if (isset($res['errcode']) && !empty($res['errcode'])) {
			$ret['is_ok'] = false;
		} else {
			// 更新数据库中的页面授权access_token记录
			$criteria = array('type' => 'oauth_access_token', 'open_id' => $res['openid']);
			$data = array('$set' => array('access_token'  => $res['access_token'], 
						 				  'expires_in'    => $res['expires_in'],
						                  'refresh_token' => $res['refresh_token'],
						                  'scope'         => $res['scope'],
						                  'ts'            => new \MongoDate(time()),
			));
			$options = array('upsert' => true);
			
			$this->_db->getMWxTokens()->update($criteria, $data, $options);
			
			$ret['is_ok'] = true;
		}
		
		return $ret;
	}
	
	
	/**
	 * 使用用户授权码code换取页面操作授权令牌access_token（用户首次授权或授权过期时）
	 * 
	 * @param string $code 用户授权码
	 * @return array $ret 返回结果
	 * 
	 * @author 安佰胜
	 */
	public function getOAuthAccessTokenByCode($code) {
		$app_id = C('wx_conf', 'app_id');
		$app_secret = C('wx_conf', 'app_secret');
		
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?".
			   "appid=$app_id&secret=$app_secret&code=$code&grant_type=authorization_code";
		
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		$ret = $res;
		if (isset($res['errcode']) && !empty($res['errcode'])) {
			$ret['is_ok'] = false;
		} else {
			// 更新数据库中的页面授权access_token记录
			$criteria = array('type' => 'oauth_access_token', 'ext_info.open_id' => $res['openid']);
			$data = array('$set' => array('token'      => $res['access_token'], 
						 				  'expires_in' => $res['expires_in'],
						                  'ext_info'   => array('refresh_token' => $res['refresh_token'],
						                  						'scope' => $res['scope']),
						                  'create_ts'  => new \MongoDate(time()),
			));
			$options = array('upsert' => true);
			
			$this->_db->getMWxTokens()->update($criteria, $data, $options);
			
			$ret['is_ok'] = true;
		}
		
		return $ret;
	}
	
	/**
	 * 通过用户授权获取页面授权access_token
	 * 
	 * @param string $scope 授权域
	 * @return string $access_token
	 * 
	 * @author 安佰胜
	 */
	public function getOAuthAccessToken($scope) {
		$code = $this->getOAuthCode($scope);
		$token_info = $this->getOAuthAccessTokenByCode($code);
		
		return $token_info['access_token'];
	}
	
	/**
	 * 获取用户信息（详细）
	 * 
	 * @param string $open_id
	 * @return array $user_info 用户信息
	 * 
	 * @author 安佰胜
	 */
	public function getUserInfo($open_id, $access_token) {
		/*$criteria = array('type' => 'oauth_access_token', 'open_id' => $open_id);
		$res = $this->_db->getMWxTokens()->findOne($criteria);
		
		// 如果页面授权access_token记录不存在，则需要用户授权
		if (empty($res)) {
			$access_token = $this->getOAuthAccessToken('snsapi_userinfo');
		} else {
			// 如果页面授权access_token过期（比官方过期时间提前100秒），则需要刷新
			if ((time()-$res['ts']->{'sec'})>$res['expires_in']-100) {
				$refresh_res = $this->getOAuthAccessTokenByRefreshToken($res['refresh_token']);
					
				// 如果refresh_token也过期，则需要重新获得用户授权
				if (!$refresh_res['is_ok'] && $refresh_res['errcode'] == '42002') {
					$access_token = $this->getOAuthAccessToken('snsapi_userinfo');
				} else {
					$access_token = $refresh_res['access_token'];
				}
			} else {
				$access_token = $res['access_token'];
			}
		}*/
		
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$open_id&lang=zh_CN";
		
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		$user_info_res = curl($options);
		
		$ret = $user_info_res;
		if (isset($ret['errcode']) && !$ret['errcode']) {
			$ret['is_ok'] = false;
		} else {
			$ret['is_ok'] = true;
		}
		
		return $ret;
	}
	
	public function addKfAccount($account) {
		if (empty($account)) {
			throw new JuneException('客服账号信息不能为空！');
		}
		
		$access_token = $this->getAccessToken();
		
		$url = "https://api.weixin.qq.com/customservice/kfaccount/add?access_token=$access_token";
		
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $account, 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	public function updateKfAccount($account) {
		if (empty($account)) {
			throw new JuneException('客服账号信息不能为空！');
		}
		
		$access_token = $this->getAccessToken();
		
		$url = "https://api.weixin.qq.com/customservice/kfaccount/update?access_token=$access_token";
		
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $account, 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	public function delKfAccount() {
		if (empty($account)) {
			throw new JuneException('客服账号信息不能为空！');
		}
		
		$access_token = $this->getAccessToken();
		
		$url = "https://api.weixin.qq.com/customservice/kfaccount/del?access_token=$access_token";
		
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $account, 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 上传客服人员头像
	 * 
	 * 头像图片文件必须是jpg格式，推荐使用640*640大小的图片以达到最佳效果
	 * 
	 * @param string $kf_account
	 * @param FILE $img_file
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	public function updateKfAvatar($kf_account, $img_file) {
		if (empty($img_file)) {
			throw new JuneException('头像文件不能为空！');
		}
		
		$access_token = $this->getAccessToken();
		
		$url = "http://api.weixin.qq.com/customservice/kfaccount/uploadheadimg?access_token=$access_token&kf_account=$kf_account";
		
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $img_file, 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	public function getKfList() {
		$access_token = $this->getAccessToken();
		
		$url = "http://api.weixin.qq.com/cgi-bin/customservice/getkflist?access_token=$access_token";
		
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true, 'kf_list' => $res['kf_list']);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
}