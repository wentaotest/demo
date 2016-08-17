<?php namespace June\Core\Session;

use June\Core\Session;

class SessionFiles extends Session {
	private $_type;
	private $_prefix;
	private $_life_time; // session最大生存周期，即gc回收周期

	private $_data;

	public function __construct($type = 'dir', $prefix = 'june_', $life_time = 3600) {
		$this->_type = $type;
		$this->_prefix = $prefix;
		$this->_life_time = $life_time;
	}

	private function _registerSessionHandler() {
		@session_set_save_handler(array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc'));
		register_shutdown_function('session_write_close');
	}
    
    /**
     * 开启session
     * 
     * @author 安佰胜
     */
	public function open() {
		//$this->_registerSessionHandler(); // TODO 调用即崩溃，待解决！！！

		session_write_close();
		
		session_name('june');//为了避免冲突，session_start之前，需要重新命名会话，否则会返回原有会话内容，并自动反序列化并填充$_SESSION全局变量
		
		@session_start();

		$this->gc(0); // 立刻回收失效SESSION
		$this->_data = & $_SESSION;
		
		return true;
	}
    
    /**
     * 关闭session
     * 
     * @author 安佰胜
     */
	public function close() {
		return true;
	}
    
    /**
     * 设置session id
     * 
     * @author 安佰胜
     */
	public function setSessionId($sid) {
		session_id($sid);
	}
    
    /**
     * 获取session id
     * 
     * @return String $session_id
     * 
     * @author 安佰胜
     */
	public function getSessionId() {
		return session_id();
	}
    
    /**
     * 设置session name
     * 
     * @author 安佰胜
     */
	public function setSessionName($snm) {
		session_name($snm);
	}
    
    /**
     * 获取session name
     * 
     * @return String $session_name
     * 
     * @author 安佰胜
     */
	public function getSessionName() {
		return session_name();
	}
    
    /**
     * 设置session 最大生命周期
     * 
     * @param Integer $life_time 生存时间，单位：秒
     * 
     * @author 安佰胜
     */
	public function setTimeout($life_time) {
		ini_set('session.gc_maxlifetime', $life_time);
	}
    
    /**
     * 获取session 最大生命周期
     * 
     * @author 安佰胜
     */
	public function getTimeout() {
		return (int) ini_get('session.gc_maxlifetime');
	}
    
    /**
     * 判断指定键名的session值是否存在
     * 
     * @return Boolean 存在与否
     * 
     * @author 安佰胜
     */
	public function has($key_name) {
		$this->open();
		$key_name = $this->_prefix . $key_name;

		if (array_key_exists($key_name, $this->_data)) {
			return true;
		} else {
			return false;
		}
	}
    
    /**
     * 获取指定键名的session值
     * 
     * @param String $key_name 键名
     * @param String $default_value 默认值
     * @return mixed $val
     * 
     * @author 安佰胜
     */
	public function get($key_name, $default_value=null) {
		$this->open();
		$key_name = $this->_prefix . $key_name;

        return isset($this->_data[$key_name]) ? $this->_data[$key_name] : $default_value;
	}
    
    /**
     * 设置指定键名的session值
     * 
     * @param String $key_name 键名
     * @param String $value 默认值
     * 
     * @author 安佰胜
     */
	public function set($key_name, $value) {
		$this->open();
		$key_name = $this->_prefix . $key_name;

		$this->_data[$key_name] = $value;
	}

	public function read($sid) {
		return '';
	}

	public function write($sid, $data) {
		return true;
	}

	public function destroy($sid) {
		return true;
	}

	public function gc($max_life_time) {
		return true;
	}

	/**
     * @return float the probability (percentage) that the GC (garbage collection) process is started on every session initialization, defaults to 1 meaning 1% chance.
     */
    public function getGCProbability() {
        return (float) (ini_get('session.gc_probability') / ini_get('session.gc_divisor') * 100);
    }

    /**
     * @param float $value the probability (percentage) that the GC (garbage collection) process is started on every session initialization.
     * @throws InvalidParamException if the value is not between 0 and 100.
     */
    public function setGCProbability($value) {
        if ($value >= 0 && $value <= 100) {
            // percent * 21474837 / 2147483647 ≈ percent * 0.01
            ini_set('session.gc_probability', floor($value * 21474836.47));
            ini_set('session.gc_divisor', 2147483647);
        } else {
            throw new Exception('GCProbability must be a value between 0 and 100 ! ');
        }
    }
}
?>