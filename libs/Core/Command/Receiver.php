<?php namespace June\Core\Command;

/**
 * Command接收器（接收并执行调用者的命令）
 * 
 * 同一个Command可以对应多个Receiver，亦可一个都没有！
 * 比如：执行输出任务的Command，可以有输出到日志的Receiver，也可以有输出到屏幕的Receiver
 * 
 *
 * @author 安佰胜
 */
abstract class Receiver {
	/**
	 * 接收器参数
	 * 
	 * @var mixed
	 */
	protected $_args;
	
	/**
	 * 接收器构造函数
	 * 
	 * @param mixed $args
	 * 
	 * @author 安佰胜
	 */
	public function __construct($args) {
		$this->_args = $args;
	}
	
	/**
	 * 接收器运行函数
	 * 
	 * @author 安佰胜
	 */
	abstract function run();
}