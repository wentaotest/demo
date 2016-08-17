<?php namespace June\Core\Cache;

use June\Core\Cache;

class MemcacheClient extends Cache {
	static private $_inst;
	
	private function __construct($cfg) {
		$this->_cfg = $cfg;
	}
	
	static public function getInstance($cfg) {
		if (empty(self::$_inst)) {
			self::$_inst = new RedisClient($cfg);
		}
	
		return self::$_inst;
	}
}
?>