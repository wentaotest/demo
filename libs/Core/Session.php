<?php namespace June\Core;

abstract class Session {
   
    /**
     * 开启session时调用
     * 
     * @author 安佰胜
     */
	abstract function open();
    
    /**
     * 关闭session时调用
     * 
     * @author 安佰胜
     */
	abstract function close();
	
	/**
	 * 读取session时调用
	 * 
	 * @param unknown $sid
	 * 
	 * @author 安佰胜
	 */
	abstract function read($sid);
	
	/**
	 * 设置session时调用
	 * 
	 * @param string $sid
	 * @param mixed $data
	 * 
	 * @author 安佰胜
	 */
	abstract function write($sid, $data);
	
	/**
	 * 销毁session时调用
	 * 
	 * @param string $sid
	 * 
	 * @author 安佰胜
	 */
	abstract function destroy($sid);
	
	/**
	 * 清理过期session时调用
	 * 
	 * @param integer $max_life_time
	 * 
	 * @author 安佰胜
	 */
	abstract function gc($max_life_time);
    
    /**
     * 设置session id
     * 
     * @author 安佰胜
     */
	abstract function setSessionId($sid);
    
    /**
     * 获取session id
     * 
     * @return String $session_id
     * 
     * @author 安佰胜
     */
	abstract function getSessionId();
    
    /**
     * 设置session name
     * 
     * @author 安佰胜
     */
	abstract function setSessionName($snm);
    
    /**
     * 获取session name
     * 
     * @return String $session_name
     * 
     * @author 安佰胜
     */
	abstract function getSessionName();
    
    /**
     * 设置session 最大生命周期
     * 
     * @param Integer $life_time 生存时间，单位：秒
     * 
     * @author 安佰胜
     */
	abstract function setTimeout($life_time);
    
    /**
     * 获取session 最大生命周期
     * 
     * @author 安佰胜
     */
	abstract function getTimeout();
    
    /**
     * 判断指定键名的session值是否存在
     * 
     * @return Boolean 存在与否
     * 
     * @author 安佰胜
     */
	abstract function has($key_name);
    
    /**
     * 获取指定键名的session值
     * 
     * @param String $key_name 键名
     * @param String $default_value 默认值
     * @return mixed $val
     * 
     * @author 安佰胜
     */
	abstract function get($key_name, $default_value=null);
    
    /**
     * 设置指定键名的session值
     * 
     * @param String $key_name 键名
     * @param String $value 默认值
     * 
     * @author 安佰胜
     */
	abstract function set($key_name, $value);
}
?>