<?php

define("__ROOT__", dirname(__FILE__));

include __ROOT__ . '/configs/CONSTANTS.php';
include __ROOT__ . '/libs/Funcs/base_funcs.php';

// 运行模式
june_run_mode('web_app');

// 调试模式
debug_mode('development');

$app = include __ROOT__ . '/bootstraps/app.php';

try {
	$app->run();
} catch (\June\Core\JuneException $e) {
	$e->display();
}
?>