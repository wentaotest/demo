<?php 
use June\Core\Controller\AppController;
use June\apps\services\WxService;

class WeixinController extends AppController {
	private $_db = null;

	public function actionBefore() {
		parent::actionBefore();
		$this->_db = june_get_apps_db_conn_pool();
	}

	public function doIndex() {
		$this->display();
	}

	/**
	 * 获取access_token
	 *
	 * @author 王文韬 2016-08-16
	 */
	public function doGetAccessToken () {
		$s_wx = WxService::getInstance();
		$res = $s_wx->getAccessToken();
		if ($res['errcode'] === 0) {
			echo 'access_token:'.$res['access_token'];
		} else {
			echo 'error_msg:'.$res['errmsg'].'/'.'error_code:'.$res['errcode'];
		}
	}

	/**
	 * 获取微信服务器地址列表
	 *
	 * @author 王文韬 2016-08-17
	 */
	public function doGetWxServerIp() {
		$s_wx = WxService::getInstance();
		$ip_list = $s_wx->getWxServerIp();
		header('Content-type:text/html;charset=utf-8');
		echo '<table border="1" cellspacing="0" cellpadding="5" style="width:960px;border-collapse:collapse; margin:30px auto;"><caption>微信服务器列表</caption><tr>';
		$i = 1;
		foreach ($ip_list as $k => $v) {
			echo "<td>$v</td>";
			if ($i % 8 == 0) {
				echo '</tr><tr>';
			}
			$i++;
		}
		echo '</tr></table>';
	}

	/**
	 * 验证签名
	 *
	 * @author 王文韬 2016-08-16
	 */
	private function _valid() {
		$echo_str = xg('echostr');

		$s_wx = WxService::getInstance();
		if ($s_wx->checkSignature()) {
			echo $echo_str;exit;
		}
	}

	/**
	 * 对微信的请求作出相应响应
	 *
	 * @author 王文韬 2016-08-16
	 */
	public function doResponse() {
		if ($this->isGet()) {
			$this->_valid();
		} else {
			$post_str = $GLOBALS['HTTP_RAW_POST_DATA'];
			
			$post_obj = simplexml_load_string($post_str, 'SimpleXMLElement', LIBXML_NOCDATA);
			$msg_type = trim($post_obj->MsgType);
			
			switch ($msg_type) {
				// case 'event':
				// 	WxEventService::getInstance()->handler($post_obj);
				// 	break;
				// default:
				// 	WxMessageService::getInstance()->response($post_obj);
				// 	break;
			}
		}
	}
}
?>