<?php
return array(

	'debug'                => 'development', // development-开发模式 production-生产模式，默认：development

	'host_name'            => 'http://127.0.0.1:8888', 

	'root_path'            => '/shopraider/platform',

	'timezone'             => 'Asia/Shanghai',

	'lang'                 => 'zh',

	'log_type'             => 'daily',

	'default_module'       => 'portal',

	'default_controller'   => 'index',

	'default_action'       => 'index',

	'session_life_seconds' => 1800, // 会话生命周期（秒）
	
	'remember_me_days'     => 7, // 永久登录天数（记住登录）
		
	'acl'                  => true, // 是否开启acl控制
		
	'module_dirs'          => array('portal'   => './apps/modules/portal', 
						            'admin'    => './apps/modules/admin',
						            'api'      => './apps/modules/api',
						            'backyard' => './apps/modules/backyard'
	), // 模块路径
	
	'session'              => array('save_handler' => 'files', // files-文件存储，redis-redis存储
					                'save_path'    => './temp', // 保存路径 ，files-'/tmp'或'C:\WINDOWS\Temp'  redis-'tcp://localhost:6379'
	), // session配置

	'fs_proxy' 			   => array('on'  => false, //是否开启代理上传
									'url' => "http://192.168.1.227:8083/miniclass/api.php?action=api.video.upload" // 代理请求的地址(on为false情况无效)

	), // 代理上传配置
	
	'queue'                => array('beanstalk' => array('persistent' => true,
														 'host' => '127.0.0.1',
														 'port' => 11300,
														 'timeout' => 1,
														 'logger' => null),
	), // 消息队列设置
);
?>