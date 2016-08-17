<?php
// 实现类的自动加载
if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
	//SPL autoloading was introduced in PHP 5.1.2
	if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
		spl_autoload_register('june_autoloader', true, true);
	} else {
		spl_autoload_register('june_autoloader');
	}
} else {
	function __autoload($classname)
	{
		june_autoloader($classname);
	}
}

/**
 * 自动加载
 *
 * @author 安佰胜
 */
function june_autoloader($class_name){
	if (class_exists($class_name)) {
		return;
	}

	$crumbs = explode('June\\', $class_name);
	$class = count($crumbs)>1 ? $crumbs[1] : $crumbs[0];

	$inc_dirs = array(
			__ROOT__ . '/libs',
			__ROOT__ . '/apps/models',
			__ROOT__ . '/apps/services',
			__ROOT__ . '/apps/queue_jobs',
			__ROOT__ . '/apps/daemon_tasks'
	);

	foreach ($inc_dirs as $dir) {
		if (strpos(get_include_path(), $dir) === false) {
			set_include_path(get_include_path(). PATH_SEPARATOR. $dir);
		}
	}
	$class = str_replace('\\', '/', $class) . '.php';

	require_once($class);
}