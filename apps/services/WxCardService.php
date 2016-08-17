<?php namespace June\apps\services;

/**
 * 微信公众号卡片服务类
 *
 * @author 安佰胜
 */
class WxCardService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_mongodb_connection();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxCardService();
		}

		return self::$_inst;
	}
}
?>