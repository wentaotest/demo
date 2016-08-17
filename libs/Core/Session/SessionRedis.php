<?php namespace June\Core\Session;

use June\Core\Session;
use June\Core\JuneException;

/**
 * 实现使用Redis存储Session
 * 
 * 注意：使用SessionRedis后session的属性不受php.ini的配置影响，只取决于实例化时的配置参数
 *
 * @author 安佰胜
 */
class SessionRedis extends Session {
	private $_prefix;
	private $_life_time; // session最大生存周期，即gc回收周期
	private $_data;
	private $_handler;

	public function __construct($prefix = 'june_', $life_time = 3600) {
		if(!class_exists("redis", false)){
			throw new JuneException("You must install phpredis extension first!");
		}
		
		$this->_prefix    = $prefix;
		$this->_life_time = $life_time;
		
		@session_set_save_handler(
				array($this, 'open'),
				array($this, 'close'),
				array($this, 'read'),
				array($this, 'write'),
				array($this, 'destroy'),
				array($this, 'gc')
		);
		
		register_shutdown_function('session_write_close');
		
		// 保存session数据并关闭当前session
		session_write_close();
		
		// 为了避免冲突，session_start之前，需要重新命名会话，否则会返回原有会话内容，并自动反序列化并填充$_SESSION全局变量
		session_name('shopraider');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Session::start()
	 */
	public function start() {
		@session_start();
		
		$this->_data = &$_SESSION;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Session::open()
	 */
	public function open() {
		if(is_resource($this->_handler)) return true;
		
		// 连接Redis服务器
		$cfg = C('web_conf', 'session.save_path');
		
		if (!empty($cfg)) {
			$cfg = substr($cfg, 6);// 'tcp://localhost:6379' 为了兼容使用配置设置session.save_handler方式为redis
			$pieces = explode(':', $cfg);
			$r_host = $pieces[0];
			$r_port = $pieces[1];
		} else {
			$r_host = 'localhost';
			$r_port = '6379';
		}
		
		$redis = new \Redis();
		$redis->connect($r_host, $r_port);
		
		$this->_handler = $redis;
		
		// 立刻清理过期的SESSION
		$this->gc(null);
		
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Session::close()
	 */
	public function close() {
		return $this->_handler->close();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Session::read()
	 */
	public function read($session_id) {
		return $this->_handler->get($session_id);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Session::write()
	 */
	public function write($session_id, $session_data) {
		return $this->_handler->setex($session_id, $this->_life_time, $session_data);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Session::destroy()
	 */
	public function destroy($session_id) {
		$this->_data = null;
		
		$this->_handler->setex($session_id, 0, $this->_data);
		
		return $this->_handler->delete($session_id) >= 1 ? true : false;
	}
    
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Session::gc()
	 */
	public function gc($life_time) {
		$this->_handler->keys('*');
		
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
		$this->_life_time = intval($life_time);
	}
    
    /**
     * 获取session 最大生命周期
     * 
     * @author 安佰胜
     */
	public function getTimeout() {
		return (int) $this->_life_time;
	}
    
    /**
     * 判断指定键名的session值是否存在
     * 
     * @return Boolean 存在与否
     * 
     * @author 安佰胜
     */
	public function has($key_name) {
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
		$key_name = $this->_prefix . $key_name;

		$this->_data[$key_name] = $value;
	}
}
?>