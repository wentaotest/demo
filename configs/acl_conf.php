<?php
/**
 * acl控制规则
 * 
 * 默认情况下：1、未允许，即禁止！2、禁止优先级 > 允许优先级
 * 
 * 'visitor' => array(
 * 			'allow' => array(
 * 					array("/^backyard[.]index[.]*$/i", true),//允许游客访问，但需要检查会话，约束范围大的条件放在前面！！！
 * 					array("/^backyard[.]index[.]login$/i", false),//允许游客访问，不需要检查会话
 * 					array("/^backyard[.]index[.]forgetpwd$/i", false),//允许游客访问，不需要检查会话
 * 			), 
 * 			'deny' => array(
 * 					"/^portal[.]member[.].*$/i",//禁止游客访问
 * 			)),
 *
 */
return array(
		'visitor' => array(
				'allow' => array(
						array("/^portal[.].*$/i", false),
						array("/^admin[.]products[.]index$/i", false),
						array("/^admin[.]agents[.]index$/i", false),
						array("/^backyard[.]index[.]login$/i", false),
				), 
				'deny' => array(
						"/^portal[.]member[.].*$/i",
				)),
		
		'member' => array(
				'allow' => array(
						array("/^portal[.].*$/i", false),
						array("/^admin[.]index[.]login$/i", false),
						array("/^admin[.]index[.]checkout$/i", false),
				), 
				'deny' => array( 
				)),
		
		'admin' => array(
				'allow' => array(
						// array("/^admin[.].*$/i", true),
						array("/^backyard[.]*$/i", true),
						array("/^backyard[.]index[.]login$/i", false),
				), 
				'deny' => array(
						"/^portal[.]member[.].*$/i",
				)),
);
?>