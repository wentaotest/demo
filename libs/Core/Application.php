<?php namespace June\Core;

class Application {
	private $_is_down = false;

	private $_di = null;

	public function __construct(&$di) {
		$this->_di = $di;
	}
	
	/**
	 * 运行应用
	 * 
	 * @author 安佰胜
	 */
	public function run() {
		// 开启debug模式
		if (false) {
			require './libs/Plugins/DebugBar/StandardDebugBar.php';
			
			$debugbar = new DebugBar\StandardDebugBar();
			$debugbarRenderer = $debugbar->getJavascriptRenderer();
			
			$debugbar["messages"]->addMessage("hello world!");
		}
		
		// 开启session
		$session = $this->_di->get('session');
		$session->open();
		
		// 解析路由信息
		$router = $this->_di->get('router');
		$router->parseRequest();
		
		// 检查进入权限（粗颗粒度）
		$need_check_session = false;
		if (C('web_conf', 'acl')) {
			$acl = $this->_di->get('acl');
			$res = $acl->filter();
			
			$need_check_session = $res['need_check_session'];
		}
		
		// 定位执行action
		$dispatcher = $this->_di->get('dispatcher');
		$dispatcher->exec($need_check_session);
	}
	
	/**
	 * 关闭应用
	 * 
	 * @author 安佰胜
	 */
	public function down() {
		$this->_is_down = true;
	}
}
?>