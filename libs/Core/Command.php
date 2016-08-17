<?php namespace June\Core;

use June\Core\Command\Receiver;
/**
 * 命令Command类
 *
 * @author 安佰胜
 */
abstract class Command {
	/**
	 * 命令参数
	 * 
	 * @var mixed
	 */
	protected $_args;
	
	/**
	 * 命令接收器
	 * 
	 * @var Receiver
	 */
	protected $_receiver = null;
	
	/**
	 * 命令构造函数
	 * 
	 * @param string $receiver_class
	 * 
	 * @author 安佰胜
	 */
	public function __construct($args, $receiver_class = null) {
		$this->_args = $args;
		
		if (!empty($receiver_class) && class_exists($receiver_class)) {
			$this->_receiver = new $receiver_class($args);
		}
	}
	
	/**
	 * 执行命令
	 * 
	 * @author 安佰胜
	 */
	abstract function execute();
	
	/**
	 * 唤起接收者执行命令
	 * 
	 * @author 安佰胜
	 */
	abstract protected function _invoke_receiver();
	
	/**
	 * 获取接收器
	 * 
	 * @author 安佰胜
	 */
	public function getReceiver() {
		return $this->_receiver;
	}
}