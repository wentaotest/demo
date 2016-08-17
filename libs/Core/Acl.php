<?php namespace June\Core;

class Acl {
	private $_router  = null;
	private $_session = null;
	private $_req     = null;
	
	/**
	 * 构造函数
	 * 
	 * @param Router $router
	 * @param Session $session
	 * 
	 * @author 安佰胜
	 */
	public function __construct(Router $router, Session $session) {
		$this->_router  = $router;
		$this->_session = $session;
		$this->_req     = $router->getRequest();
	}
	
	/**
	 * acl过滤器
	 * 
	 * 注意：只验证准入规则，不验证会话合法性和有效性，将会话检测延迟到控制器方法执行前
	 * 
	 * @throws \Exception
	 * 
	 * @author 安佰胜
	 */
	public function filter() {
		$login_user = $this->_session->get('login_user');
		
		// 判断访问用户的身份
		$ac_ty = 'visitor';
		if (!empty($login_user)) {
			$ac_ty = isset($login_user['access_type']) && !empty($login_user['access_type']) ? $login_user['access_type'] : 'visitor';
		}
		
		// 根据访问用户身份判断是否有进入的权限及是否需要检查会话
		$routes = $this->_router->getRoutes();
		$action = implode('.', $routes);
		$is_allowed = false;
		$need_check = true;
		
		// 允许条件检测
		$allow_list = C('acl_conf', "$ac_ty.allow");
		foreach ($allow_list as $allow) {
			if (preg_match($allow[0], $action)) {
				$is_allowed = true;
				$need_check = $allow[1];
			}
		}
		
		// 拒绝条件检测
		$deny_list = C('acl_conf', "$ac_ty.deny");
		foreach ($deny_list as $deny) {
			$is_denied = preg_match($deny, $action);
			if ($is_denied) {
				$is_allowed = false;
				break;
			} else {
				$is_allowed = true;
			}
		}
		
		if (!$is_allowed) {
			throw new \Exception("非法访问，操作未授权！");
		}
		
		return array('allow_access' => true, 'need_check_session' => $need_check);
	}
}