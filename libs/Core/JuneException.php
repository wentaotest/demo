<?php namespace June\Core;

class JuneException extends \Exception {
	
	const REQ_ERR_PARAMS_MISSING = 1; // 请求错误：参数丢失
	const REQ_ERR_PARAMS_INVALID = 2; // 请求错误：参数非法
	const REQ_ERR_NO_PERMISSION  = 3; // 请求错误：没有权限
	const REQ_ERR_SESSION_FAILED = 4; // 请求错误：会话失效
	
	const CONN_ERR_MONGO_DB  = 10; // 连接错误：MongoDB
	const CONN_ERR_MYSQL_DB  = 11; // 连接错误：MySQLDB
	const CONN_ERR_REDIS     = 12; // 连接错误：Redis
	const CONN_ERR_BEANSTALK = 13; // 连接错误：Beanstalk
	
	const DB_ROW_NOT_EXISTS     = 20; // 数据查询：记录不存在
	const DB_UPSERT_ERROR       = 21; // 数据更新：插入或更新失败
	const DB_TIME_OVERDUE       = 22; // 数据验证：已过期
	const DB_ROW_ALREADY_EXISTS = 23; // 数据查询：记录已存在
	
	const MEMBER_PROPERTY_NOT_EXISTS = 30; // 自定义类：成员属性不存在
	const MEMBER_METHOD_NOT_EXISTS   = 31; // 自定义类：成员方法不存在
	
	const LOTTO_ENDED = 110; // 参与活动：活动已结束(或商品已过期) 注意：该错误码不能用于其他地方！！
	
	// PHP错误类型
	const PHP_ERR_NAMES = "return array(
			'1'    => 'Fatal Error',
			'2'    => 'Warning',
			'4'    => 'Parse Error',
			'8'    => 'Notice',
			'16'   => 'Core Error',
			'32'   => 'Core Warning',
			'64'   => 'Compile Error',
			'128'  => 'Compile Warning',
			'256'  => 'User Error',
			'512'  => 'User Warning',
			'1024' => 'User Notice',
			'2047' => 'All(~E_SRTICT)',
			'2048' => 'Strict',
			'4096' => 'E_RECOVERABLE_ERROR',
			'8191' => 'E_ALL',
	);";
	
	// PHP错误提示
	const PHP_ERR_HINTS = "return array(
			'1'    => '运行时致命的错误。不能修复的错误。停止执行脚本。',
			'2'    => '运行时非致命的错误。没有停止执行脚本。',
			'4'    => '编译时的解析错误。解析错误应该只由解析器生成。',
			'8'    => '运行时的通知。脚本发现可能是一个错误，但也可能在正常运行脚本时发生。',
			'16'   => 'PHP启动时的致命错误。这就如同 PHP核心的 E_ERROR。',
			'32'   => 'PHP启动时的非致命错误。这就如同 PHP核心的 E_WARNING。',
			'64'   => '编译时致命的错误。这就如同由 Zend 脚本引擎生成的 E_ERROR。',
			'128'  => '编译时非致命的错误。这就如同由 Zend 脚本引擎生成的 E_WARNING。',
			'256'  => '用户自定义的错误。',
			'512'  => '用户自定义的警告。',
			'1024' => '用户自定义的提醒。',
			'2047' => '所有错误，但不包括E_STRICT。',
			'2048' => '编码标准化警告，允许PHP建议如何修改代码以确保最佳的互操作性向前兼容性。',
			'4096' => '可捕获的致命错误。类似 E_ERROR，但可被用户定义的处理程序捕获。',
			'8191' => '所有错误和警告，除级别 E_STRICT 以外。',
	);";
	
	/**
	 * 返回PHP语法错误编号
	 * 
	 * @param integer $err_code
	 * @return string
	 * 
	 * @author Vincent An
	 */
	static public function getPhpErrName($err_code) {
		$names = eval(JuneException::PHP_ERR_NAMES);
		
		return isset($names["$err_code"]) ? $names["$err_code"] : '0';
	}
	
	/**
	 * 返回PHP语法错误提示
	 *
	 * @param integer $err_code
	 * @return string
	 *
	 * @author Vincent An
	 */
	static public function getPhpErrHint($err_code) {
		$hints = eval(JuneException::PHP_ERR_HINTS);
		
		return isset($hints["$err_code"]) ? $hints["$err_code"] : '';
	}
	
	/**
	 * 显示异常信息页面
	 * 
	 * @author 安佰胜
	 */
	public function display() {
		$exception_vars = array('message' => $this->getMessage(),
								'previous' => $this->getPrevious(),
								'code' => $this->getCode(),
								'file' => $this->getFile(),
								'line' => $this->getLine(),
								'trace' => $this->getTrace(),
								'trace_string' => $this->getTraceAsString(),
		);
		
		render_view("./libs/Core/Exception/exception_page.php", $exception_vars);
	}
}

?>