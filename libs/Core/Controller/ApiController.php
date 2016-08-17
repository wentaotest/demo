<?php namespace June\Core\Controller;

use June\Core\Controller;

class ApiController extends Controller {
	private $_succeeded = false;
	private $_result = array('token' => 'this is a token!');// TODO：可用来验证api调用的安全性
	private $_err_code = NULL;
	private $_err_msg = 'unkown error';
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Controller::actionBefore()
	 */
	public function actionBefore() {
		// TODO 此处验证api请求的合法性，如果暂停api服务亦可在此处中断
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Controller::actionAfter()
	 */
	public function actionAfter() {
		
	}
	
	public function setSucceeded($is_ok) {
		$this->_succeeded = $is_ok;
	}
	
	public function getSucceeded() {
		return $this->_succeeded;
	}
	
	public function setResult($result) {
		if (!empty($result)) {
			$this->_result = array_merge($this->_result, $result);
		}
	}
	
	public function getResult() {
		return $this->_result;
	}
	
	public function setErrCode($err_code) {
		$this->_err_code = $err_code;
	}
	
	public function getErrCode() {
		return $this->_err_code;
	}
	
	public function setErrMsg($err_msg) {
		$this->_err_msg = $err_msg;
	}
	
	public function getErrMsg() {
		return $this->_err_msg;
	}
	
	/**
	 * 生成输入结果数组
	 * 
	 * @author 安佰胜
	 */
	private function _genResult() {
		$action = $this->getModuleName().".".$this->getControllerName().".".$this->getActionName();
		
		$result = array("action" => $action, "succeeded" => $this->_succeeded, 'result' => $this->getResult());
		
		if (!$this->_succeeded) {
			if (!empty($this->_err_msg)) {
				$result['errmsg'] = $this->_err_msg;
			}
			
			if (!empty($this->_err_code)) {
				$result['errcode'] = $this->_err_code;
			}
		} else {
			$result['errcode'] = 0;
			$result['errmsg'] = '';
		}
		
		return json_encode_ex($result);
	}
	
	/**
	 * 输出json格式结果
	 * 
	 * @author 安佰胜
	 */
	public function output() {
		$content = $this->_genResult();
		
		ob_clean();
		
		/*if (extension_loaded("zlib") && !headers_sent()
				&& array_key_exists('HTTP_ACCEPT_ENCODING', $_SERVER)
				&& strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip")) {
			ob_start(function() {
				$content = gzencode($content, 0); // 0-未压缩，9-最高级别压缩
				header("Content-Encoding: gzip");
				header("Vary: Accept-Encoding");
			});
		}*/
		
		header('Content-Length: ' . strlen($content));
		header('Connection: Close');
		
		echo $content;
		
		ob_end_flush();
		
		$mode = C('web_conf', 'debug');
		
		if ($mode == 'development') {
			error_log(json_encode($content));
		}
		
		exit;
	}
}
?>