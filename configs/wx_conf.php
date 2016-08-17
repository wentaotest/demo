<?php

return array(
		// 微信公众号信息
		'app_id'     => 'wx09e16137501c4074',
		'app_secret' => 'd4624c36b6795d1d99dcf0547af5443d', // 需要严格保密！！！定期重置
		'token'      => 'feisha900525', // 微信接入验证口令

		// 微信商户信息
		'mch_id'  => '1236777202',
		'mch_key' => '924f05f103decc12250c310168536146', // 需要严格保密！！！定期重置
		'rootca'  => './wx_api_cert/rootca.pem',
		'apiclient_cert' => './wx_api_cert/apiclient_cert.pem',
		'apiclient_key'  => './wx_api_cert/apiclient_key.pem',
);

?>