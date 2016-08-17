<?php namespace June\apps\services;

use June\Core\DIContainer;
use June\apps\services\TripleDesService;
use June\Core\DbConn\MongoDbConn;

class AuthService {
	static private $_inst;
	private $_account;
	private $_password;
	private $_db;
	private $_tc_name;

	private function __construct($db, $tc_name = null) {
		$this->_db = $db;
		$this->_tc_name = $tc_name;
	}

	static public function getInstance($db, $tc_name = null) {
		if (empty(self::$_inst)) {
			self::$_inst = new AuthService($db, $tc_name);
		}

		return self::$_inst;
	}
    
    /**
     * 验证用户身份信息
     * 
     * @param String $account 用户账号
     * @param String $password 密码（明文）
     * @return Array $auth_res 验证结果
     * 
     * @author 安佰胜
     */
	public function authenticate($account, $password) {
		$this->_account  = $account;
		$this->_password = $this->encryptPwd($password);
		
		// 获取用户信息
		$user_info = $this->_getUserInfo(array('username' => $account), $this->_tc_name);
		
		if (isset($user_info['password']) && $user_info['password'] === $this->_password) {
			$auth_res = array('code' => 1, 'user_info' => $user_info);
		} else {
			$auth_res = array('code' => 0, 'user_info' => null);
		}

		return $auth_res;
	}
	
	/**
	 * 使用临时口令验证用户信息
	 * 
	 * @param string $user_id
	 * @param string $rmb_token
	 * 
	 * @author 安佰胜
	 */
	public function authRmbToken($user_id, $rmb_token) {
		$criteria = array('_id' => new \MongoId($user_id));
		
		// 获取用户信息
		$user_info = $this->_getUserInfo($criteria, $this->_tc_name);
		
		if (isset($user_info['rmb_token']) && $user_info['rmb_token'] == $rmb_token && $user_info['rmb_token_expired_ts'] >= new \MongoDate()) {
			$auth_res = array('code' => 1, 'user_info' => $user_info);
		} else {
			$auth_res = array('code' => 0, 'user_info' => null);
		}
		
		return $auth_res;
	}
    
    /**
     * 登录并保存用户会话
     * 
     * @param Array $user_info 用户信息
     * @param String $remember_me 是否记住登录
     * @return Boolean 
     * 
     * @author 安佰胜
     */
	public function checkIn($user_info, $remember_me = false) {
		// 创建并保存会话
		$di = DIContainer::getInstance();

		$session = $di->get('session');
		$session->start();
		
		// 如果是用户记住登录，则会话过期延长至7天（但这并不保险，因为系统会不定时回收session，所以还要安排重新登录）
		if ($remember_me) {
			$expired_ts = time() + C('web_conf', 'remember_me_days') * 24 * 3600;
			
			// 将登录信息保存到cookie
			$this->rememberMe($user_info);
		} else {
			$expired_ts = time() + C('web_conf', 'session_life_seconds');
		}
		
		// 密码不能存到会话中
		unset($user_info['password']);
		
		// 设置cookie的过期时间，php.ini默认为0，即关闭浏览器即清除cookie
		ini_set("session.cookie_lifetime", C('web_conf', 'remember_me_days') * 24 * 3600);

		$session->set('login_user', $user_info);
		$session->set('session_expired_ts', $expired_ts);
		
		// 更新用户登录信息
		if ($this->_tc_name) {
			$m_users = $this->_db->getModel($this->_tc_name);
			$criteria = array('_id' => $user_info['_id']);
			$data = array('login_ts' => new \MongoDate(), 'login_ip' => get_client_ip());
			
			$m_users->update($criteria, array('$set' => $data));
		}
		
		return true;
	}
    
    /**
     * 退出登录并清除用户会话
     * 
     * @return Boolean
     * 
     * @author 安佰胜
     */
	public function checkOut() {
		$di = DIContainer::getInstance();

		$session = $di->get('session');

		$session->destroy(session_id());
		
		// 同时要消除自动登录（记住登录）
		setcookie("auth", "", time()-1);

		return true;
	}
	
	/**
	 * 记住登录
	 * 
	 * 注意！在cookie中会保存三条信息，分别是：
	 * 
	 * 1、身份标识（保存用户的_id）
	 * 2、登录令牌（不是密码，令牌有时效性）
	 * 3、登录令牌有效期（即cookie记录有效期）
	 * 
	 * @param array $user_info 用户信息
	 * @author 安佰胜
	 */
	public function rememberMe($user_info) {
		$user_id  = $user_info['_id']->{'$id'};
		$password = $user_info['password'];
		$login_ts = $user_info['login_ts']->{'sec'};
		$timeout  = C('web_conf', 'remember_me_days') * 24 * 3600;
		
		// 生成令牌，规则：md5(用户名+密码字符串的一半+上一次登录时间戳)
		$token = md5($user_info['username'] . substr($password, 0, 15) . $login_ts);
		
		// 将令牌及过期时间保存到用户记录中
		$criteria = array('_id' => $user_info['_id']);
		$data = array('rmb_token' => $token, 'rmb_token_expired_ts' => new \MongoDate(time()+$timeout));
		
		$this->_db->getModel($this->_tc_name)->update($criteria, $data);
		
		// 设置cookie的过期时间，php.ini默认为0，即关闭浏览器即清除cookie
		ini_set("session.cookie_lifetime", $timeout);
		
		// 保存cookie
		setcookie('auth', "$user_id:$token", time() + $timeout);
	}
    
    /**
     * 获取认证用户信息
     * 
     * @return Array $auth_info 
     * 
     */
	public function getAuthInfo() {
		$di = DIContainer::getInstance();

		$session = $di->get('session');

		$auth_info = array('user_info' => $session->get('login_user'),
						   'session_expired_ts' => $session->get('session_expired_ts'));

		return $auth_info;
	}
	
	/**
	 * 是否已经登录
	 * 
	 * @author 安佰胜
	 */
	public function isLogin() {
		$auth_info = $this->getAuthInfo();
		
		if (!empty($auth_info['user_info'])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 登录会话是否超时
	 * 
	 * @author 安佰胜
	 */
	public function isTimeout() {
		$auth_info = $this->getAuthInfo();
		
		$expired_ts = DIContainer::getInstance()->get('session')->get('session_expired_ts');
		
		if ($expired_ts >= time()) {
			return false;
		} else {
			return true;
		}
	}
    
    /**
     * 获取用户信息
     * 
     * @param String $criteria 查找条件
     * @param String $tc_name 用户集合（表）名称
     * @return Mixed
     * 
     * @author 安佰胜
     */
	private function _getUserInfo($criteria = array(), $tc_name) {
		$m_tc = $this->_db->getModel($tc_name);
		
		$criteria['enable'] = true;
		$fields = array('_id' => true, 'username' => true, 'password' => true, 
						'type' => true, 'verified' => true, 'first_lgn' => true, 'login_ip' => true,
				        'login_ts' => true, 'rmb_token' => true, 'rmb_token_expired_ts' => true);
		
		$user_info = $m_tc->findOne($criteria, $fields);
		
		if (!empty($user_info)) {
			$user_type = isset($user_info['type']) ? '_'.strtolower($user_info['type']) : '';
			$user_info['namespace'] = $tc_name . $user_type; // 此处的命名空间用以分隔不同角色的同名用户
		}

		return $user_info;
	}
    
    /**
     * 使用加密规则对字符串加密
     * 
     * @param String $pwd 待加密的明文密码字符串
     * @return String 已加密的密码字符串（数据库存储的是该字符串！！！）
     * 
     * @author 安佰胜
     */
	public function encryptPwd($pwd) {
		$des3 = new TripleDesService();
        return md5($des3->encrypt(strtolower($pwd)));
	}
    
    /**
     * 生成用户账号激活链接地址
	 * @param mongoId $uid用户的id号码
	 * @param String  $tc_name 结合名(表名)
	 * @return String 返回用户激活的链接地址
	 *
	 * @author  孟生伟
     */
    public function generateActivationUrl($uid, $tc_name) {
    	
    	//获取当前用户的激活码
    	$code = $this->generateAcitvationCode($uid,$tc_name);
		$data = array('acitvationcode'=>$code);
		//将用户的验证码存入数据库
		$m_tc = $this->_db->getModel($tc_name);
		
		$res = $m_tc->update($uid,$data,array());
		
		foreach($uid as $key=>$value){
			$uid = "$value";
		}
		$url = C('web_conf', 'host_name').C('web_conf','root_path').'/'."index.php?action=portal.login.login".'&'."id=".$uid.'&'."code=".$code;
		
		return $url;
    }
    
    /**
     * 验证用户账号激活链接地址
     */
    public function checkActivationUrl($uid, $activation_url, $tc_name) {
      
	
    }
    
      /**
     * 生成用户激活码
	 * @param mongoId $uid用户的id号码
	 * @param String  $tc_name 结合名(表名)
	 * @return String 加密后的username和password
	 *
	 * @author  孟生伟
     */
    public function generateAcitvationCode($uid, $tc_name) {
       	//获取用户信息
    	$m_tc = $this->_db->getModel($tc_name);
		$criteria = array('_id'=>$uid,'enable'=>false);
		$fields = array('username'=>true,'password');
    	$userinfo = $m_tc ->findOne($criteria,$fields);
		$res = $this->encryptPwd($userinfo['username'].$userinfo['password']);
		
        return $res;
    }
    
    /**
     * 验证用户账号激活码
	 * @param String $uid 用户uid
	 * @param String $activation_code 用户的激活码
	 * @param String $tc_name  集合名(表名)
	 * @return  true or false
	 * 
	 * @author 孟生伟
     */
    public function checkActivationCode($uid, $activation_code, $tc_name) {
    	if($uid){
    		$id = new \MongoId($uid);
    	}else{exit;}
		
		$m_tc = $this->_db->getModel($tc_name);
		$fields = array('acitvationcode'=>true, 'overdue_ts'=>true);
		$res = $m_tc ->findOne(array("_id"=>$id),$fields);
		$overdue_ts = $res['overdue_ts']->{'sec'};//过期时间
		$time = time(); //当天时间
		$code = $res['acitvationcode'];
		
		//判断用户的激活码是否正确
		if($code == $activation_code && $overdue_ts >= $time){
			//将状态修改为verified修改为true
			$data = array('verified'=>'true');
			$row = $m_tc->update($id,$data,array());
			$res = 1;
		}else{
			$res = 0;
		}
		return $res;
    }
	
	
}