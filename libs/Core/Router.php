<?php namespace June\Core;

use June\Core\Request;

class Router {
	// 配置信息
	private $_cfg = array('sub_domain_supported'=>false);

	// 请求对象
	private $_req = null;

	// 路由规则表
	private $_rules = array('GET'=>array(), 'POST'=>array());

	// 解析后的路由信息列表 [module, controller, action]
	private $_routes = array();

	// 解析后的参数信息列表
	private $_params = array();

	// 保存cookie信息
	private $_cookies = array();

	// 默认路由设置
	private $_defaults = array();

	// 子域名映射表
	private $_domain_maps = array('www.baidu.com' => 'portal', 'localhost' => 'portal');

	public function __construct(Request $req) {
		$this->_req = $req;
		$this->_cookies = $req->getCookies();
		$this->_defaults['module'] = C('web_conf', 'default_module');
		$this->_defaults['controller'] = C('web_conf', 'default_controller');
		$this->_defaults['action'] = C('web_conf', 'default_action');
	}

	/**
	 * 添加路由规则，以确定路由信息分界 路由信息包括module、controller和action 支持参数表达式
	 * 
	 * $this->addRoute(array('GET', 'POST'), '/post/index');
	 * $this->addRoute('GET', array('/post/<mongo_id:\w{n}>' => '/post/view/<mongo_id:\w{n}>'));
	 * $this->addRoute('GET', '/post/view');
	 * 
	 * @param mixed $method 请求方式
	 * @param mixed $route 路由规则
	 * @return object Router
	 */
	public function addRoute($method, $route) {
		$methods = array();
		if (is_string($method)) {
			$methods[] = $method;
		}

		foreach ($methods as $m_name) {
			$m_name = strtoupper($m_name);

			if (!isset($this->_rules[$m_name])) {
				$this->_rules[$m_name] = array();
			}
			array_push($this->_rules[$m_name], $route);
			$this->_rules[$m_name] = array_unique($this->_rules[$m_name]);
		}

		return $this;
	}

	/**
	 * 解析用户请求，得到路由信息和参数列表
	 *
	 * @return object Router
	 */
	public function parseRequest() {
		$method = $this->_req->method();
		$query_str = $this->_req->getQueryString();

		$rules = $this->_rules[$method];

		foreach ($rules as $rule) {
			if (is_array($rule)) {
				$keys = array_keys($rule);
				$real_rule = $rule[$keys[0]];
				$rule = $keys[0];

				preg_match_all("/([A-Za-z\/]+\/)*/i", $real_rule, $matches, PREG_PATTERN_ORDER);
				$real_route = isset($matches[1][0]) ? rtrim($matches[1][0], '/') : '';
			}

			// 根据rule生成参数正则表达式
			$regx = "/([A-Za-z\/]+\/)*<([a-zA-Z0-9\\\{\}\[\]:\-\|_]*)>/i";
			preg_match_all($regx, $rule, $matches, PREG_PATTERN_ORDER);
			

			$route = isset($matches[1][0]) ? rtrim($matches[1][0], '/') : '';
			
			$p_crews = array();
			if (!empty($matches[2])) {
				foreach ($matches[2] as $match) {
					list($p_name, $p_val_pattern) = explode(':', $match);
					array_push($p_crews, array('name' => $p_name, 'pattern' => $p_val_pattern, 'value' => null));
				}
			}

			// 生成query_string验证的正则表达式
			$pattern = "/".str_replace('/', '\/', $route);;
			foreach ($p_crews as $crew) {
				$pattern .= "\/($crew[pattern])";					
			}
			$pattern .= '/i';

			// 匹配路由规则
			if(preg_match_all($pattern, $query_str, $query_matches, PREG_PATTERN_ORDER)) {
				for ($i = 1; $i < count($query_matches); $i++) {
					$this->_params[$p_crews[$i-1]['name']] = isset($query_matches[$i][0]) ? $query_matches[$i][0] : '';
				}

				if (isset($real_route)) {
					$this->_routes = $this->_parseRoute($real_route);
				} else {
					$this->_routes = $this->_parseRoute($route);
				}
				
				break;
			}	
		}

		// 自定义PathInfo模式路由规则无法匹配，则尝试匹配传统路由模式
		if (empty($this->_routes) && empty($this->_params)) {
			eval('$req_vars = $_'.$this->_req->method().';');

			$route = "";
			if (isset($req_vars['action'])) {
				$route = str_replace('.', '/', $req_vars['action']);
				unset($req_vars['action']);
			} elseif (isset($_GET['action'])) {
			    $route = str_replace('.', '/', $_GET['action']);
			}

			$this->_params = $req_vars;
			$this->_routes = $this->_parseRoute($route);
		}

		return $this;
	}

	/**
	 * 解析路由信息
	 *
	 * @param string $route 路由信息字符串
	 * @return array [module, controller, action]
	 */
	private function _parseRoute($route) {
		if ($this->_cfg['sub_domain_supported']) {
			$domain = $this->_req->getHostName();
			$module =  array_key_exists($domain, $this->_domain_maps) ? $this->_domain_maps[$domain] : '';
		}

		$pieces = explode('/', trim($route, '/'));
		if (count($pieces) == 3) {
			list($module, $controller, $action) = $pieces;
		} elseif (count($pieces) == 2) {
			list($controller, $action) = $pieces;
			$module = isset($module)&&!empty($module) ? $module : $this->_defaults['module'];
		} else {
			$module = isset($module)&&!empty($module) ? $module : $this->_defaults['module'];
			$controller = $this->_defaults['controller'];
			$action = $this->_defaults['action'];
		}

		return compact('module', 'controller', 'action');
	}

	public function getRequest() {
		return $this->_req;
	}

	public function getRoutes() {
		return $this->_routes;
	}
}
?>