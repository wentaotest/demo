<?php namespace June\Core;

use June\Core\Router;

class Dispatcher {
	private $_router = null;
	private $_req = null;
	private $_module_dirs = array();
	
	/**
	 * 调度分发器构造函数
	 * 
	 * @param Router $router
	 * 
	 * @author 安佰胜
	 */
	public function __construct(Router $router) {
		$this->_router = $router;
		$this->_req = $router->getRequest();
		$this->_module_dirs = C('web_conf', 'module_dirs');
	}
    
    /**
     * 按照解析的路由信息，实例化对应的控制器，并执行对应的acticon方法
     * 
     * @param boolean $need_check_session 是否需要检查session会话
     * 
     * @author 安佰胜
     */
	public function exec($need_check_session = false) {
		extract($this->_router->getRoutes());

		$ctrl_file_path = $this->_module_dirs[$module] . '/controllers/' . $controller . '.php';

		if (file_exists($ctrl_file_path)) {
			include $ctrl_file_path;

			// 实例化控制器类
			$ctrl_nm = ucfirst($controller) . 'Controller';
			if (!class_exists($ctrl_nm)) {
				throw new JuneException("Class '$ctrl_nm' does not exist !", 1);
			}
			$ctrl_obj = new $ctrl_nm();
			
			// 检查会话的合法性和有效性
			if ($need_check_session) {
				$ctrl_obj->checkSession();
			}

			// Action被执行前
			$ctrl_obj->actionBefore();

			// 执行Action
			$action_name = 'do' . ucfirst($action);
			if (!method_exists($ctrl_obj, $action_name)) {
				throw new JuneException("Action '$action_name' in '$ctrl_nm' does not exist !", 1);
			}
			$ctrl_obj->$action_name();

			// Action被执行后
			$ctrl_obj->actionAfter();
		} else {
			throw new JuneException("File required by Controller Class '$controller' does not exist	!", 1);
		}
	}



}
?>