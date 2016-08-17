<?php namespace June\Core\Queue;

/**
 * 消息队列中的任务类
 *
 * @author 安佰胜
 */

abstract class JuneQueJob {
	// 传入的参数数组
	protected $_args = array();
	
	public function __construct($args = array()) {
		$this->_args = array_merge($args, $this->_args);
	}
	
	public function __destruct() {
		$this->_args = null;
	}
	
	/**
	 * 任务运行前的准备工作
	 * 
	 * @author 安佰胜
	 */
	abstract function beforePerform();
	
	/**
	 * 任务逻辑部分
	 * 
	 * @author 安佰胜
	 */
	abstract function perform($job_id);
	
	/**
	 * 任务运行后的清理工作
	 * 
	 * @author 安佰胜
	 */
	abstract function afterPerform();
}