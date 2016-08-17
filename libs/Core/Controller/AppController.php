<?php namespace June\Core\Controller;

use June\Core\Controller;
use June\Core\JuneException;

class AppController extends Controller {
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Controller::actionBefore()
	 */
	public function actionBefore() {
		$this->assign('login_user', $this->getLoginUserInfo());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Controller::actionAfter()
	 */
	public function actionAfter() {
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Controller::checkSession()
	 */
	public function checkSession() {
		$ret = parent::checkSession();
		
		if ($ret === false) {
			$_SESSION['history_action'] =  $this->getControllerName() . '.' . $this->getActionName();
			
			$this->message('会话过期！', '会话失效，请重新登录！', 'index.login', 5, 'default');
		}
	}
	
	/**
	 * 获取登录用户信息
	 * 
	 * @author 安佰胜
	 */
	public function getLoginUserInfo() {
		return $this->_di->get('session')->get('login_user');
	}
	
	/**
	 * 获取登录用户的MongoId
	 * 
	 * @author 安佰胜
	 */
	public function getLoginUserId() {
		$lgn_user_info = $this->getLoginUserInfo();
		
		return empty($lgn_user_info) ? null : $lgn_user_info['_id'];
	}
	
	/**
	 * 向页面传递变量列表
	 * 
	 * @param string $name
	 * @param mixed $value
	 * 
	 * @author 安佰胜
	 */
	public function assign($name, $value) {
		$this->_vars["$name"] = $value;
	}
	
	/**
	 * 显示视图文件
	 * 
	 * @param string $view
	 * @throws \Exception
	 * 
	 * @author 安佰胜
	 */
	public function display($view=null) {
		$mod_nm  = $this->getModuleName();
		$ctrl_nm = $this->getControllerName();
		
		if (is_null($view)) {
			$view = strtolower($this->getActionName());
		} else {
			if (strstr($view, '.')) {
				$crumbs = explode('.', $view);
			} else {
				$crumbs = array($view);
			}
			
			if (count($crumbs) == 3) {
				$mod_nm  = $crumbs[0];
				$ctrl_nm = $crumbs[1];
				$view    = $crumbs[2];
			} elseif (count($crumbs) == 2) {
				$mod_nm  = $this->getModuleName();
				$ctrl_nm = $crumbs[0];
				$view    = $crumbs[1];
			}
		}
	
		$view_path = "./apps/modules/$mod_nm/views/$ctrl_nm/$view.php";
	
		if (file_exists($view_path)) {
			ob_start();
			
			render_view($view_path, $this->_vars);
			
			$last_error = error_get_last();
			if (empty($last_error)) {
				ob_end_flush();
			}
		} else {
			throw new JuneException("视图文件不存在！", 1);
		}
	}
	
	/**
	 * 消息提示页面
	 * 
	 * @param string $message 消息
	 * @param string $desc 描述
	 * @param string $redirect_action 跳转链接
	 * @param number $delay_seconds 延迟秒数
	 * @param string $style 样式 default、warning、error、success
	 * 
	 * @author 安佰胜
	 */
	public function message($message, $desc=null, $redirect_action=null, $delay_seconds=5, $style='default') {
		if (empty($message)) {
			throw new JuneException('消息内容不能为空！');
		}
		
		$module = $this->getModuleName();
		
		$message_view = "./apps/modules/$module/views/message.php";
		
		if (file_exists($message_view)) {
			if (!empty($redirect_action)) {
				// 根据action获取重定向全路径
				if (strstr($redirect_action, '.')) {
					$crumbs = explode('.', $redirect_action);
				} else {
					$crumbs = array($redirect_action);
				}
					
				if (count($crumbs) == 3) {
					$full_action = "action=$crumbs[0].$crumbs[1].$crumbs[2]";
				} elseif (count($crumbs) == 2) {
					$full_action = "action=$module.$crumbs[0].$crumbs[1]";
				} else {
					$controller = $this->getControllerName();
					$full_action = "action=$module.$controller.$crumbs[0]";
				}
				
				$redirect_url = "index.php?" . $full_action;
			} else {
				$redirect_url = "";
			}
			
			// 准备传递的变量列表
			$vars = array('message' => $message,
						  'desc'    => $desc,
						  'style'   => $style,
						  'redirect_url'  => $redirect_url,
						  'delay_seconds' => $delay_seconds,
			);
			
			// 渲染视图
			render_view($message_view, $vars);
			exit;
		} else {
			throw new JuneException('消息视图文件不存在！');
		}
	}
}
?>