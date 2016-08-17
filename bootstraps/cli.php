<?php

require_once __ROOT__ . "/bootstraps/june_autoloader.php";
require_once __ROOT__ . '/libs/Funcs/base_funcs.php';

use June\Core\DIContainer;

// 运行模式
june_run_mode('bck_cli');

// 系统全局设置
date_default_timezone_set('PRC');

// 准备服务容器
$di = DIContainer::getInstance();

$di->registerSingleton('queue', 'June\Core\Queue\JuneQueue', array());


?>