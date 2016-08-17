<?php
return array(
		'cache_enable' => true,
		'cache_type' => 'redis',
		'persistent_conn' => false,
		'redis' => array(
				'mode' => 0, // 工作模式，0-单点，1-主从（读写分离：主写、从读）
				'master' => array('host' => '127.0.0.1', 'port' => 6379, 'auth' => 'xx'), // 主节点
				'slaves' => array(
						
				), // 从节点列表
				'ttl' => 60, // 临时缓存的time to live，单位：秒
		),
		'memcache' => array(),
);
?>