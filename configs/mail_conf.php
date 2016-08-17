<?php
return array(
	'method' => 'smtp', // 'mail'-使用php的mail函数发送邮件，'smtp'-使用smtp发送邮件
	'from' => array('mail_address' => 'zhangshangweidu_hr@126.com',
					'sender_name' => 'zhangshangweidu_hr'),
	'smtp' => array('host' => 'smtp.126.com',
					'port' => 25,
					'auth' => true,
					'username' => 'zhangshangweidu_hr',
					'password' => 'qinjin123',
					),
	);
?>