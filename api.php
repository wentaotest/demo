<?php

header("Content-type:text/html;charset=utf-8");

define("__ROOT__", dirname(__FILE__));

include __ROOT__ . '/configs/CONSTANTS.php';
include __ROOT__ . '/libs/Funcs/base_funcs.php';

$app = include __ROOT__ . '/bootstraps/api.php';

// 调试模式
debug_mode(C('web_conf', 'debug'));

try {
	$app->run();
} catch (\June\Core\JuneException $e) {
	$err_msg = "服务器捕获异常：" . $e->getMessage() . " File: " . $e->getFile() . " Line: " . $e->getLine();
	$ret_msg = array(
			'action' => '',
			'succeeded' => false,
			'result' => array(
					'token' => 'this is a token!',
			),
			'errmsg' => $err_msg,
			'errcode' => $e->getCode(),
	);
	
	@ob_end_clean();
	ob_start();
	
	echo json_encode($ret_msg);
	
	ob_end_flush();
}
?>