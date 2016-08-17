<?php

use June\Core\Controller\AppController;
use June\apps\services\WxManagerService;
use June\apps\services\WxMessageService;
use June\apps\services\WxEventService;
use June\apps\services\WxUserService;

class WechatController extends AppController {
	private $_db = null;

	public function actionBefore() {
		parent::actionBefore();
		$this->_db = june_get_apps_db_conn_pool();
	}
	
	/**
	 * 验证微信接入的合法性
	 * 
	 * @author 安佰胜
	 */
	private function _valid() {
		$echo_str = xg('echostr');
		
		$s_wx_manager = WxManagerService::getInstance();
		
		if ($s_wx_manager->checkSignature()) {
			echo $echo_str;
			exit;
		}
	}
	
	/**
	 * 响应用户消息或事件
	 * 
	 * @author 安佰胜
	 */
	public function doResponse() {
		if ($this->isGet()) {
			$this->_valid();
		} else {
			$post_str = $GLOBALS['HTTP_RAW_POST_DATA'];
			
			$post_obj = simplexml_load_string($post_str, 'SimpleXMLElement', LIBXML_NOCDATA);
			$msg_type = trim($post_obj->MsgType);
			
			switch ($msg_type) {
				case 'event':
					WxEventService::getInstance()->handler($post_obj);
					break;
				default:
					WxMessageService::getInstance()->response($post_obj);
					break;
			}
		}
	}
	
	/**
	 * 生成带参数的微信公众号二维码
	 * 
	 * @author 安佰胜
	 */
	public function doGenQrCodeImg() {
		if ($this->isPost()) {
			$type = xr('type');
			$scene_val = xr('scene_val');
			
			$s_wx_manager = WxManagerService::getInstance();
			
			$ticket_info = $s_wx_manager->getQrCodeTicket($type, $scene_val);
			
			if (!empty($ticket_info['ticket'])) {
				$this->redirect("portal.wechat.genqrcodeimg", array('ticket' => $ticket_info['ticket']));
			} else {
				$this->message("微信二维码获取失败！", 'genqrcodeimg');
			}
		} else {
			$ticket = xg('ticket');
			
			$this->assign('ticket', $ticket);
			$this->display();
		}
	}
	
	/**
	 * 获取生成的带参数的公众号二维码
	 * 
	 * @throws \Exception
	 * 
	 * @author 安佰胜
	 */
	public function doGetQrCodeImg() {
		$ticket = xg('ticket');
		
		if (empty($ticket)) {
			throw new \Exception('微信二维码获取凭证不能为空！');
		}
		
		header('Content-Type:image/jpg');
		echo WxManagerService::getInstance()->getQrCodeImage($ticket);
		exit;
	}
	
	/**
	 * 添加行业消息模板
	 * 
	 * @author 安佰胜
	 */
	public function doAddMsgTpl() {
		if ($this->isPost()) {
			$tpl_id  = xp('tpl_id');
			$title   = xp('title');
			$content = xp('content');
			$params  = xp('params');
			$sample  = xp('sample');
			$industry_id = xp('industry_id');
			$industry_nm = xp('industry_nm');
			
			$tpl_data = array('tpl_id'  => $tpl_id,
							  'title'   => $title,
					          'content' => $content,
					          'params'  => $params,
							  'sample'  => $sample,
					          'industry_id' => $industry_id,
					          'industry_nm' => $industry_nm,
							  'create_ts'   => new \MongoDate(time()),
							  'update_ts'   => new \MongoDate(time()),
							  'enable'      => true,
			);
			
			$res = $this->_db->getMWxMsgTpls()->insertOneGetId($tpl_data);
			
			if ($res) {
				$this->redirect("portal.wechat.msgtpldetail", array('id' => $res));
			} else {
				$this->redirect("portal.wechat.msgtpls", array('is_ok' => false));
			}
		} else {
			$this->display();
		}
	}
	
	/**
	 * 微信消息模板详情
	 * 
	 * @author 安佰胜
	 */
	public function doMsgTplDetail() {
		$id = xg('id');
		
		$criteria = array('_id' => new \MongoId($id));
		
		$tpl_info = $this->_db->getMWxMsgTpls()->findOne($criteria);
		
		$params_arr = explode(',', $tpl_info['params']);
		
		$this->assign('tpl_info', $tpl_info);
		$this->assign('params_arr', $params_arr);
		$this->display();
	}
	
	/**
	 * 测试发送模板消息
	 * 
	 * @throws \Exception
	 * 
	 * @author 安佰胜
	 */
	public function doSendTplMsg() {
		$to     = xp('to');
		$tpl_id = xp('tpl_id');
		$url    = xp('url');
		$data   = xp('data');
		$_id    = xp('_id'); // 模板记录的MongoId
		
		if (empty($to) || empty($tpl_id) || empty($url) || empty($data)) {
			throw new \Exception('参数丢失！');
		}
		
		$res = WxMessageService::getInstance()->sendTplMessage($to, $tpl_id, $url, $data);
		
		if ($res) {
			$this->redirect("portal.wechat.msgtpldetail", array('is_ok' => true, 'id' => $_id));
		} else {
			$this->redirect("portal.wechat.msgtpldetail", array('is_ok' => true, 'id' => $_id));
		}
	}
	
	/**
	 * 微信消息模板列表
	 * 
	 * @author 安佰胜
	 */
	public function doMsgTpls() {
		$criteria = array('enable' => true);
		
		$msg_tpls = $this->_db->getMWxMsgTpls()->find($criteria);
		
		$this->assign('msg_tpls', $msg_tpls);
		$this->display();
	}
	
	/**
	 * 获取关注者列表（从微信服务器获取）
	 * 
	 * @author 安佰胜
	 */
	public function doGetFollowers() {
		$start_open_id = xg('start_open_id');
		
		$followers = WxUserService::getInstance()->getFollowersList($start_open_id);
		
		$this->assign("followers", $followers);
		$this->assign('start_open_id', $start_open_id);
		$this->display();
	}
	
	/**
	 * 获取关注者基本信息（从微信服务器获取）
	 * 
	 * @author 安佰胜
	 */
	public function doGetFollowerInfo() {
		$open_id = xg('open_id');
		$start_open_id = xg('start_open_id');
		
		$follower_info = WxUserService::getInstance()->getFollowerUserInfo($open_id);
		
		$this->assign("follower_info", $follower_info);
		$this->assign('start_open_id', $start_open_id);
		$this->display();
	}
	
	/**
	 * 创建微信自定义菜单
	 * 
	 * @author 安佰胜
	 */
	public function doCreateMenu() {
		if ($this->isPost()) {
			$menu = xp("menu");
			
			$menu_data = array("button" => $menu);
			
			$res = WxManagerService::getInstance()->createMenu($menu_data);
			
			if (empty($res['errcode'])) {
				$this->redirect("portal.wechat.getmenu", array('is_ok' => true));
			} else {
				$msg = "微信自定义菜单创建失败！原因：[$res[errcode]]$res[errmsg]";
				
				$this->message($msg, 'createmenu', 10);
			}
		} else {
			$this->display();
		}
	}
	
	/**
	 * 获取微信自定义菜单
	 * 
	 * @author 安佰胜
	 */
	public function doGetMenu() {
		$menu_info = WxManagerService::getInstance()->getMenu();
		
		$this->assign('menu_info', json_encode_ex($menu_info, true));
		$this->display();
	}
	
	/**
	 * 删除微信自定义菜单
	 * 
	 * @author 安佰胜
	 */
	public function doDeleteMenu() {
		$res = WxManagerService::getInstance()->deleteMenu();
		
		if (isset($res['errcode']) && !empty($res['errcode'])) {
			$this->message("微信自定义菜单删除成功！", 'getmenu', 10);
		} else {
			$this->message("微信自定义菜单删除失败！", 'getmenu', 10);
		}
	}
	
	/**
	 * 网页获取用户信息授权
	 * 
	 * @author 安佰胜
	 */
	public function doUserOAuth() {
		$scope = xg('scope');
		
		$s_wx_manager = WxManagerService::getInstance();
		
		$code = $s_wx_manager->getOAuthCode($scope);
		
		$access_token = $s_wx_manager->getOAuthAccessTokenByCode($code);
		
		$user_info = $s_wx_manager->getUserInfo($access_token['openid']);
		
		var_dump($user_info);exit;
	}
	
	/**
	 * 微信js_api测试页面
	 * 
	 * @author 安佰胜
	 */
	public function doWxJsApi() {
		$sign_package = WxManagerService::getInstance()->getJsApiSignPackage();
		
		$this->assign('sign_package', $sign_package);
		$this->display();
	}
	
	/**
	 * 该方法仅供测试阶段使用，上线后需要删除！上线后需要删除！！上线后需要删除！！！
	 * 
	 * @author 安佰胜
	 */
	public function doGiveMeAccessToken() {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		echo $access_token;exit;
	}
	
	public function doIndex() {

	}
	
	/**
	 * 已开发微信功能目录
	 * 
	 * @author 安佰胜
	 */
	public function doMenu() {
		$this->display();
	}

	public function doNext() {
		echo "OK";exit;
	}
} 