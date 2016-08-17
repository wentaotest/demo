<?php namespace June\Core;

abstract class Cache {
	/**
	 * 建立一个缓存连接
	 *
	 * @return boolean $is_success
	 * 
	 * @author 安佰胜
	 */
	abstract function connect();
	
	/**
	 * 建立一个缓存持久连接
	 * 
	 * @return boolean $is_success
	 *
	 * @author 安佰胜
	 */
	abstract function pconnect();
	
	/**
	 * 关闭缓存连接
	 * 
	 * @param string $tag 标签：master-关闭主连接、slave-关闭从连接、all-关闭所有连接
	 * @return boolean $is_success
	 *
	 * @author 安佰胜
	 */
	abstract function close($tag = 'all');
	
	/**
	 * 查看键名是否存在
	 * 
	 * @param string $key
	 * @return boolean 
	 * 
	 * @author 安佰胜
	 */
	abstract function exists($key);
	
	/**
	 * 设置键值，如果存在就覆写
	 * 
	 * @param string $key
	 * @param string $val
	 * @param integer $timeout
	 * @return boolean $is_ok
	 * 
	 * @author 安佰胜
	 */
	abstract function set($key, $val, $timeout);
	
	/**
	 * 按照键名获取键值
	 * 
	 * @param string $key
	 * @return mixed $ret_val 如果不存在则返回false
	 * 
	 * @author 安佰胜
	 */
	abstract function get($key);
	
	/**
	 * 对存在的键值进行加法操作，键名不存在返回false
	 * 
	 * @param string $key
	 * @param integer $inc_val
	 * @return boolean $is_ok 
	 * 
	 * @author 安佰胜
	 */
	abstract function increment($key, $inc_val = 1);
	
	/**
	 * 对存在的键值进行减法操作，键名不存在返回false
	 * 
	 * @param string $key
	 * @param integer $dec_val
	 * @return boolean $is_ok
	 * 
	 * @author 安佰胜
	 */
	abstract function decrement($key, $dec_val = 1);
	
	/**
	 * 删除缓存记录
	 * 
	 * @param string $key
	 * @return boolean $is_ok
	 * 
	 * @author 安佰胜
	 */
	abstract function delete($key);
	
	/**
	 * 批量删除缓存记录
	 * 
	 * @param array $keys
	 * @return boolean $is_success
	 * 
	 * @author 安佰胜
	 */
	abstract function batchDelete($keys);
	
	/**
	 * 清空缓存
	 * 
	 * @return boolean $is_ok
	 * 
	 * @author 安佰胜
	 */
	abstract function flush();
}

?>
