<?php

use June\apps\services\QRcodeService;
use June\Core\Controller\AppController;

class IndexController extends AppController {
	private $_db = null;

	public function actionBefore() {
		parent::actionBefore();
		$this->_db = june_get_apps_db_conn_pool();
	}

	public function doIndex() {
		echo 1;
	}
	
	public function doQrcode() {
		header('Content-Type:image/png');
		
		$content = array('msg' => '这是一条测试消息');
		
		echo QRcodeService::genQRcode(json_encode_ex($content));
		
		exit;
	}
	
	public function doUrl2Qrcode() {
		$this->display();
	}
	
	public function doCreateTinyUrl() {
		$long_url = xr('long_url');
// 		$long_url = "http://image.baidu.com/i?ct=503316480&z=0&tn=baiduimagedetail&word=%B6%CC%CD%F8%D6%B7&in=25321&cl=2&lm=-1&pn=3&rn=1";
		
		$options = array('method' => 'POST',
				'url' => 'http://dwz.cn/create.php',
				'post_data' => array('url' => $long_url));
		$short_url = curl($options);
		
		echo json_encode($short_url);exit;
	}
	
	public function doCreateQrCode4Url() {
		$url = xr('url');
		
		QRcodeService::genQRcode($url);
	}

	public function doNext() {
		echo "OK";exit;
	}
} 

?>