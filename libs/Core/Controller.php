<?php namespace June\Core;

use June\Core\DIContainer;

/**
 * 控制器父类
 *
 * @author 安佰胜
 */
abstract class Controller {
	protected $_di = null;
	protected $_vars = array();
	
	/**
	 * 控制器构造函数
	 * 
	 * @author 安佰胜
	 */
	public function __construct() {
		$this->_di = DIContainer::getInstance();
	}
	
	/**
	 * 控制器方法执行前的操作
	 * 
	 * @author 安佰胜
	 */
	abstract function actionBefore();
	
	/**
	 * 控制器方法执行后的操作
	 * 
	 * @author 安佰胜
	 */
	abstract function actionAfter();
	
	/**
	 * 获取控制器类名
	 * 
	 * @return string $class_name
	 * 
	 * @author 安佰胜
	 */
	public function getControllerClass() {
		return get_class($this);
	}
	
	/**
	 * 获取路由信息
	 * 
	 * @return array $routes
	 * 
	 * @author 安佰胜
	 */
	public function getRoutes() {
		return $this->_di->get('router')->getRoutes();
	}
	
	/**
	 * 获取模块名称
	 * 
	 * @return string 
	 * 
	 * @author 安佰胜
	 */
	public function getModuleName() {
		$routes = $this->_di->get('router')->getRoutes();
		return $routes['module'];
	}
	
	/**
	 * 获取控制器名称
	 * 
	 * @return string
	 * 
	 * @author 安佰胜
	 */
	public function getControllerName() {
		$routes = $this->_di->get('router')->getRoutes();
		return $routes['controller'];
	}
	
	/**
	 * 获取方法名称
	 * 
	 * @return string
	 * 
	 * @author 安佰胜
	 */
	public function getActionName() {
		$routes = $this->_di->get('router')->getRoutes();
		return $routes['action'];
	}
	
	/**
	 * 获取请求方法
	 * 
	 * @author 安佰胜
	 */
	public function getRequestMethod() {
		return $this->_di->get('request')->method();
	}
	
	/**
	 * 是否为AJAX请求
	 * 
	 * @author 安佰胜
	 */
	public function isAjax() {
        return $this->_di->get('request')->isAjax();
	}
	
	/**
	 * 是否为POST请求
	 * 
	 * @author 安佰胜
	 */
	public function isPost() {
        return $this->_di->get('request')->isPost();
	}
	
	/**
	 * 是否为GET请求
	 * 
	 * @author 安佰胜
	 */
	public function isGet() {
        return $this->_di->get('request')->isGet();
	}
	
	/**
	 * 项目内控制器方法跳转
	 * 
	 * @param mixed $action
	 * @param array $params
	 * 
	 * @author 安佰胜
	 */
	public function redirect($action, $params=array()) {
		if (strstr($action, '.')) {
			$crumbs = explode('.', $action);
		} else {
			$crumbs = array($action);
		}

		if (count($crumbs) == 3) {
			$full_action = "action=$crumbs[0].$crumbs[1].$crumbs[2]";
		} elseif (count($crumbs) == 2) {
			$module = $this->getModuleName();
			$full_action = "action=$module.$crumbs[0].$crumbs[1]";
		} else {
			$controller = $this->getControllerName();
			$module = $this->getModuleName();
			$full_action = "action=$module.$controller.$crumbs[0]";
		}

		$params_str = "";
		if (is_array($params) && !empty($params)) {
			foreach ($params as $key => $value) {
				$params_str .= "&$key=$value";
			}
		}

		$url = "index.php?$full_action".$params_str;
		
		$this->redirectUrl($url);
	}
	
	/**
	 * 任意重定向跳转
	 * 
	 * @param string $url
	 * 
	 * @author 安佰胜
	 */
	public function redirectUrl($url) {
		header("location:{$url}");
	}
	
	/**
	 * 检查并刷新会话
	 * 
	 * 注意：
	 * 
	 * 1、如果会话合法有效，则延长其生命周期，否则注销该会话
	 * 2、不需要验证会话的访问请求不会触发会话检查（即不会刷新会话生命周期）
	 * 
	 * @return boolean $ret 是否合法有效
	 * 
	 * @author 安佰胜
	 */
	public function checkSession() {
		$session = $this->_di->get('session');
		$session->start();

		$expired_ts = $session->get('session_expired_ts');
		
		$ret = false;
		if ($expired_ts >= time()) {
			$session->set('session_expired_ts', time() + C('web_conf', 'session_life_seconds'));
			$ret = true;
		} else {
			$session->destroy(session_id());
		}
		
		return $ret;
	}
	
	/**
	 * 运行命令（Command模式，此时的Controller充当Invoker）
	 * Command模式可以将调用者和被调用者解耦，并对被调用者的异构性进行了封装
	 * 
	 * @param Command $cmd
	 * 
	 * @author 安佰胜
	 */
	public function runCommand($cmd) {
		$output = $cmd->execute();
		
		return $output;
	}
		
}
?>