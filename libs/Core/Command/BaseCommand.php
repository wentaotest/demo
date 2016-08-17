<?php namespace June\Core\Command;

use June\Core\Command;

class BaseCommand extends Command {
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Command::execute()
	 */
	public function execute() {
		$output = $this->_args;
		
		if (!empty($this->_receiver)) {
			$output = $this->_invoke_receiver();
		} else {
			if (is_array($this->_args)) {
				$output = json_encode($this->_args);
			}
		}
		
		return $output;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Command::_invoke_receiver()
	 */
	protected function _invoke_receiver() {
		return $this->_receiver->run();
	}
	
}