<?php

return array(
	'mongodb' => array(
		'mode' => 0, // 工作模式：0-单点、1-主从（从机备份）、2-主从（读写分离）、3-复制集
		'db_name' => 'shopraider_db',
		'fs_name' => 'shopraider_fs',
		'master' => array('server' => "mongodb://10.232.71.105:27017",
						  'options' => array("connect" => true)
		),
		'slaves' => array(array('server' => "mongodb://127.0.0.1:37017",
								'options' => array("connect" => true)),
						  array('server' => "mongodb://127.0.0.1:47017",
								'options' => array("connect" => true)),
						  array('server' => "mongodb://127.0.0.1:57017",
								'options' => array("connect" => true)),
		),
	),
	'fastdfs' => array(),
	'mysql' => array(
                'master' => array('host' => '127.0.0.1',
                                  'port' => '3306',
                                  'user' => 'root',
                                  'pwd' => '123456',
                				  'is_persistent' => false,
                ),
                'dbname' => 'shopraider_db'
    ),

);

?>