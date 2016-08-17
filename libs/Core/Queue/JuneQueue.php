<?php namespace June\Core\Queue;

use June\Core\Queue;
use June\Core\JuneException;

/**
 * 基于beanstalk的消息队列
 * 
 * @todo 支持多个beanstalk单例进程（多服务器消息队列）
 *
 * @author 安佰胜
 */
class JuneQueue extends Queue {
	
	// 任务执行最高优先级，数字越小，优先级越高
	const MAX_PRIORITY = 0;
	
	// 任务执行最低优先级，数字越大，优先级越低
	const MIN_PRIORITY = 4294967295;
	
	public $connected = false;
	
	protected $_config = array();
	
	protected $_connection;
	
	/**
	 * 构造函数
	 *
	 * @param array $config 配置项如下:
	 *        - 'persistent'  是否为持久连接，默认：true
	 *        - 'host'        Beanstalk服务器地址，默认：127.0.0.1
	 *        - 'port'        Beanstalk服务器端口，默认：11300
	 *        - 'timeout'     worker运行job的超时时间（允许执行的最长时间），默认：1秒
	 *        - 'logger'      兼容PSR-3日志的对象实例
	 * @return void
	 *
	 * @author 安佰胜
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
				'persistent' => true,
				'host' => '127.0.0.1',
				'port' => 11300,
				'timeout' => 1,
				'logger' => null
		);
	
		$this->_config = array_merge($defaults, $config);
	}
	
	/**
	 * 析构函数
	 *
	 * @author 安佰胜
	 */
	public function __destruct() {
		$this->disconnect();
	}
	
	/**
	 * 建立与beanstalk服务器的连接
	 *
	 * @return boolean 是否连接成功
	 *
	 * @author 安佰胜
	 */
	public function connect() {
		if (isset($this->_connection)) {
			$this->disconnect();
		}
	
		$function = $this->_config['persistent'] ? 'pfsockopen' : 'fsockopen';
		$params = array($this->_config['host'], $this->_config['port'], &$err_code, &$err_msg);
	
		if ($this->_config['timeout']) {
			$params[] = $this->_config['timeout'];
		}
		$this->_connection = @call_user_func_array($function, $params);
	
		if (!empty($err_code) || !empty($err_msg)) {
			$this->error("{$err_code}: {$err_msg}");
		}
	
		$this->connected = is_resource($this->_connection);
	
		if ($this->connected) {
			stream_set_timeout($this->_connection, -1);
		}
	
		return $this->connected;
	}
	
	/**
	 * 断开与beanstalk的连接
	 *
	 * @author 安佰胜
	 */
	public function disconnect() {
		if (!is_resource($this->_connection)) {
			$this->connected = false;
		} else {
			$this->write('quit');
			$this->connected = !fclose($this->_connection);
	
			if (!$this->connected) {
				$this->_connection = null;
			}
		}
	
		return !$this->connected;
	}
	
	/**
	 * 输出至错误日志
	 *
	 * @param array $message
	 *
	 * @author 安佰胜
	 */
	public function error($message) {
		if ($this->_config['logger']) {
			$this->_config['logger']->error($message);
		}
	}
	
	/**
	 * 向beanstalk服务器写数据
	 *
	 * @param string $data
	 * @return integer|boolean 如果发生错误则返回false，否则返回发送数据的字节数
	 */
	public function write($data) {
		if (!$this->connected) {
			$message = 'No connection found while writing data to beanstalk server !';
			$message .= "Config Info : " . json_encode($this->_config);
			throw new JuneException($message);
		}
	
		$data .= "\r\n";
		return fwrite($this->_connection, $data, strlen($data));
	}
	
	/**
	 * 从beanstalk服务器读数据
	 *
	 * @param integer $length 读取数据的字节长度
	 * @return string|boolean 如果发生错误则返回false，否则返回读取的数据内容
	 */
	public function read($length = null) {
		if (!$this->connected) {
			$message = 'No connection found while reading data from beanstalk server !';
			$message .= "Config Info : " . json_encode($this->_config);
			throw new JuneException($message);
		}
	
		if ($length) {
			if (feof($this->_connection)) {
				return false;
			}
			$data = stream_get_contents($this->_connection, $length + 2);
			$meta = stream_get_meta_data($this->_connection);
	
			if ($meta['timed_out']) {
				$message = 'Connection timed out while reading data from beanstalk server !';
				throw new JuneException($message);
			}
			$packet = rtrim($data, "\r\n");
		} else {
			$packet = stream_get_line($this->_connection, 16384, "\r\n");
		}
	
		return $packet;
	}
	
	/**
	 * 创建任务(job)内容
	 * 
	 * @param string $job_class 任务类名
	 * @param array $args 任务类实例化参数
	 * @throws JuneException
	 * @return string 任务(job)内容
	 * 
	 * @author 安佰胜
	 */
	public static function createJob($job_class, $args = null) {
		if (!class_exists($job_class)) {
			$message = 'Job Class not exists !';
			throw new JuneException($message);
		}
		
		if($args !== null && !is_array($args)) {
			$message = 'Supplied $args must be an array !';
			throw new JuneException($message);
		}
		
		$job_data = array('job_class' => $job_class, 'args' => $args);
		
		return json_encode($job_data);
	}
	
	/**
	 * 将任务(job)发送到(put)消息队列(queue)中
	 *
	 * @param integer $pri 任务的执行优先级
	 * @param integer $delay 任务延时加入ready队列的时间，这段时间内任务状态为delayed
	 * @param integer $ttr worker运行job的超时时间，最小为1秒，如果worker不能在指定时间内完成job，
	 * job将被迁移回READY状态，供其他worker执行。要小心worker操纵的数据有可能被加工过两次以上，所以必要情况下需要实现事务机制，
	 * 同时，worker执行过程中也要有超时处理逻辑，一般情况下ttr设定值不要太小，至少5秒以上。
	 * @param string|array $data 任务内容.
	 * @return integer|boolean 如果错误则返回false，否则返回任务(job)的id.
	 *
	 * @author 安佰胜
	 */
	public function put($pri = 0, $delay = 0, $ttr = 5, $data) {
		if (empty($data)) {
			$message = "Invalid Job Data ! Job Data should not be empty !";
			
			throw new JuneException($message);
		} else {
			$data = is_array($data) ? json_encode($data) : $data;
		}
		
		$this->write(sprintf("put %d %d %d %d\r\n%s", $pri, $delay, $ttr, strlen($data), $data));
		$status = strtok($this->read(), ' ');
	
		switch ($status) {
			// 任务插入成功
			case 'INSERTED':
				return (integer) strtok(' '); // job id
	
				// 任务插入成功，但内存即将耗尽，beanstalk尝试提升队列中已有任务的运行优先级
			case 'BURIED':
				return (integer) strtok(' '); // job id
					
				// job内容结尾缺少回车换行符号，因为write方法自动添加CRLF，所以这种情况不会发生！
			case 'EXPECTED_CRLF':
					
				// job内容过大
			case 'JOB_TOO_BIG':
					
				// beanstalk服务器进入drain模式，只排空任务不接收任务
			case 'DRAINING':
	
			default:
				$this->error($status);
				return false;
		}
	}
	
	/**
	 * 切换管道(tude)，如果没有使用该函数，则默认将任务(job)添加到名为'default'的管道(tude)中
	 *
	 * @param string $tube 管道名称，最长不超过200字节，如果不存在，则自动创建
	 * @return string|boolean 如果失败则返回false，否则返回管道名称
	 */
	public function useTube($tube) {
		$this->write(sprintf('use %s', $tube));
		$status = strtok($this->read(), ' ');
	
		switch ($status) {
			case 'USING':
				return strtok(' ');
			default:
				$this->error($status);
				return false;
		}
	}
	
	/**
	 * 将某个管道(tude)暂停一定时间，暂不取出运行新的任务(job)
	 *
	 * @param string $tube 被暂停的管道名称
	 * @param integer $delay 暂停时间，单位：秒
	 * @return boolean
	 */
	public function pauseTube($tube, $delay) {
		$this->write(sprintf('pause-tube %s %d', $tube, $delay));
		$status = strtok($this->read(), ' ');
	
		switch ($status) {
			// 成功暂停
			case 'PAUSED':
				return true;
					
				// 管道不存在
			case 'NOT_FOUND':
			default:
				$this->error($status);
				return false;
		}
	}
}
