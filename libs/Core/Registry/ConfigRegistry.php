<?php namespace June\Core\Registry;

use June\Core\Registry;

class ConfigRegistry extends Registry {
	private $_values = array();
    private static $instance;
  
    private function __construct() {}

    static function getInstance() {
        if (!isset(self::$instance)) {
        	self::$instance = new self();
        }
        return self::$instance;  
    }

    public function get($key) {
    	if (isset($this->_values[$key])) {
    		return $this->_values[$key];
    	} else {
    		return NULL;
    	}
    }

    public function set($key, $val) {
    	$this->_values[$key] = $val;
    }
}
?>