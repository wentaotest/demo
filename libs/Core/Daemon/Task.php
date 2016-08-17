<?php namespace June\Core\Daemon;

/**
 * 守护进程中的执行任务类
 *
 * @author 安佰胜
 */
abstract class Task {
	// 传入的参数数组
	protected $_args = array();
	
	public function __construct($args) {
		$this->_args = $args;
	}
	
	/**
	 * 任务运行主函数，子类须实现任务逻辑
	 * 
	 * @author 安佰胜
	 */
	abstract function run();
}
