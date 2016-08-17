<?php

use June\Core\DIContainer;
use June\Core\Application;

require_once __ROOT__ . "/bootstraps/june_autoloader.php";

// 运行模式
june_run_mode('web_app');

// 系统全局设置
date_default_timezone_set('PRC');

// 准备服务容器
$di = DIContainer::getInstance();

$di->registerSingleton('router', 'June\Core\Router');
$di->registerSingleton('request', 'June\Core\Request');
$di->registerSingleton('dispatcher', 'June\Core\Dispatcher');

if (C('web_conf', 'session.save_handler') == 'redis') {
	$di->registerSingleton('session', 'June\Core\Session\SessionRedis');
} else {
	$di->registerSingleton('session', 'June\Core\Session\SessionFiles');
}

// 因为无法明确Acl类构造函数的参数类型，所以直接通过参数列表传递依赖关系
$di->registerSingleton('acl', 'June\Core\Acl', array($di->get('router'), $di->get('session')));

$router = $di->get('router');
$router->addRoute('GET', array("/post/<mongo_id:\w{2}>/<page:\w{2}>" => "/post/view/<mongo_id:\w{2}>/<page:\w{2}>"));

$di->registerSingleton('queue', 'June\Core\Queue\JuneQueue');

// 创建应用实例
$app = new Application($di);

return $app;

?>