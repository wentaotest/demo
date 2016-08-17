<?php namespace June\Core;

/**
 * 守护进程类
 *
 * @author 安佰胜
 */

class Daemon {
	
	private $_pid = null;
	
	private $_pid_file = "/var/run/php_june_daemon.pid";
	
	private $_log_file = "/var/log/php_june_daemon/error.log";
	
	private $_worker_cnt = 0;
	
	private $_tasks = array();
	
	/**
	 * 构造函数
	 * 
	 * @throws JuneException
	 * @author Vincent An
	 */
	public function __construct() {
		if (!file_exists($this->_pid_file)) {
			try {
				touch($this->_pid_file);
			} catch (\Exception $e) {
				throw new JuneException($e->getMessage());
			}
		}
		
		if (!file_exists($this->_log_file)) {
			try {
				//touch($this->_log_file);
			} catch (\Exception $e) {
				throw new JuneException($e->getMessage());
			}
		}
	}
	
	/**
	 * 创建守护进程（主进程）
	 * 
	 * @author 安佰胜
	 */
	private function _daemonize() {
		$pid = pcntl_fork();
		
		if ($pid == -1) {
			// fork失败
			throw new Exception('fork failed !');
		} elseif ($pid > 0) {
			// 父进程退出，让子进程成为孤儿(orphan),子进程将被1号进程收养
			exit(0);
		}
		
		// 创建新的会话并使当前进程成为进程组长，摆脱原有会话、进程组及终端的控制，成为最终的守护进程(daemon)
		if (-1 === posix_setsid()) {
			throw new Exception("setsid fail");
		}
		
		// 记录主进程号
		$this->_write_pid();
	}
	
	/**
	 * 启动工作子进程
	 * 
	 * @param integer $count 工作子进程数量
	 * 
	 * @author 安佰胜
	 */
	public function start($count = 1) {
		pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"), false);
		pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"), false);
		pcntl_signal(SIGUSR1, array(__CLASS__, "signalHandler"), false);
		pcntl_signal(SIGUSR2, array(__CLASS__, "signalHandler"), false);
		
		// 创建守护进程（主进程）
		$this->_daemonize();
		
		// 创建工作子进程
		while ($this->_worker_cnt < $count) {
			$this->_forkOneWorker();
		}
		
		// 监控工作子进程
		$this->_monitorWorkers();
	}
	
	/**
	 * 创建一个工作进程
	 * 
	 * @throws Exception
	 * @author Vincent An
	 */
	private function _forkOneWorker() {
		$pid = pcntl_fork();
	
		if ($pid > 0) {
			// 记录工作子进程号
			$this->_write_pid($pid);
				
			$this->_worker_cnt++;
				
			echo "\n\r" . "fork new worker: " . $pid . "\n\r";
		} elseif (0 === $pid) {
			echo "\n\r" . "begin worker proccess !" . "\n\r";
			// 执行工作进程任务
			$this->_workerProceed(getmypid());
		} else {
			throw new Exception("forkOneWorker fail !");
		}
	}
	
	/**
	 * 工作子进程执行任务
	 * 
	 * @param number $pid
	 * 
	 * @author Vincent An
	 */
	private function _workerProceed($pid) {
		while (true) {
			pcntl_signal_dispatch(); // 接收到信号时，调用注册的signalHandler()
		
			try {
				$this->_runTasks();
			} catch (JuneException $e) {
				echo $e->getMessage();
			}
		
			// 为避免过多占用CPU资源，随机暂停一定时间
			$intval = rand(1,9)*100 + rand(1,9)*10 + rand(1,9);
			usleep($intval);
		}
	}
	
	/**
	 * 监控工作子进程状态（如果有异常退出，则重新创建一个子进程）
	 * 
	 * @author Vincent An
	 */
	private function _monitorWorkers() {
		while(true) {
			pcntl_signal_dispatch();
			
			// 监听等待任意与调用进程组ID相同的子进程是否有退出
			$status = 0;
			$pid = pcntl_waitpid(0, $status, WUNTRACED);
			
			pcntl_signal_dispatch();
			
			// 如果有工作子进程退出
			if ($pid > 0) {
				echo $pid . "---------$status----------left worker cnt:" . $this->_worker_cnt;
				$this->_worker_cnt--;
				$this->_forkOneWorker();
			}
			
			// 为避免过多占用CPU资源，随机暂停一定时间
			$intval = rand(1,9)*100 + rand(1,9)*10 + rand(1,9);
			usleep($intval);
		}
	}
	
	/**
	 * 停止所有工作进程
	 * 
	 * @author 安佰胜
	 */
	public function stop() {
		
		$res = false;
		
		if (file_exists($this->_pid_file)) {
			$pids = file_get_contents($this->_pid_file);
			
			$pid_arr = explode(',', $pids);
			
			if (!empty($pid_arr)) {
				file_put_contents($this->_pid_file, '');
				
				foreach ($pid_arr as $pid) {
					$res = posix_kill(intval($pid), 9);
				}
			}
		}
		
		return $res;
	}
	
	/**
	 * 查看守护进程状况
	 * 
	 * @author 安佰胜
	 */
	public function status() {
		if (file_exists($this->_pid_file)) {
			$pids = file_get_contents($this->_pid_file);
				
			$pid_arr = explode(',', $pids);
			
			if (!empty($pid_arr)) {
				$cnt = count($pid_arr) - 1;
				$output = "\nDaemon processes count: " . $cnt . "\n\n";
			} else {
				$output = "\nNone daemon processes working!\n\n";
			}
		} else {
			$output = "\nNone daemon processes working!\n\n";
		}
		
		echo $output;
	}
	
	/**
	 * 添加进程任务（支持按序运行多任务）
	 * 
	 * @param array $task 任务内容，字段如下：
	 * 			- type string 类还是函数，如：function=函数，class=类
	 * 			- name string 任务类名或函数名
	 * 			- args array  参数列表
	 * 
	 * @author 安佰胜
	 */
	public function addTask($task = array()) {
		if (empty($task) || empty($task['type']) || empty($task['name'])) {
			$message = 'Task Invalid ! ';
			throw new JuneException($message);
		}
		
		$this->_tasks[] = $task;
	}
	
	/**
	 * 清空进程任务
	 * 
	 * @author 安佰胜
	 */
	public function clearTasks() {
		$this->_tasks = array();
	}
	
	/**
	 * 记录运行中的进程id
	 * 
	 * @author 安佰胜
	 */
	private function _write_pid($pid=null) {
		if (!file_exists($this->_pid_file)) {
			try {
				touch($this->_pid_file);
			} catch (\Exception $e) {
				throw new JuneException($e->getMessage());
			}
			
		}
		
		$pid = empty($pid) ? getmypid() : $pid;
		
		$content = file_get_contents($this->_pid_file) . $pid . ',';
		
		file_put_contents($this->_pid_file, $content);
	}
	
	/**
	 * 运行所有任务
	 * 
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _runTasks() {
		if (is_array($this->_tasks) && count($this->_tasks)) {
			foreach ($this->_tasks as $task) {
				switch ($task['type']) {
					case 'function':
						$func = $task['name'];
							
						if (function_exists($func)) {
							try {
								$func($task['args']);
							} catch (\Exception $e) {
								$message = $e->getMessage();
								throw new JuneException($message);
							}
			
						} else {
							$message = 'Task Function "' . $func . '" not exists ! ';
							throw new JuneException($message);
						}
							
						break;
					case 'class':
						$class = $task['name'];
						
						if (class_exists($class)) {
							try {
								$inst = new $class($task['args']);
								
								// 启动任务
								$inst->run();
							} catch (\Exception $e) {
								$message = $e->getMessage();
								throw new JuneException($message);
							}
						}
						
						break;
				}
			}
		} else {
			// 没有可执行的任务，空载！！！
		}
	}
	
	/**
	 * 信号量处理函数
	 * 
	 * @param integer $signal
	 * 
	 * @author 安佰胜
	 */
	public function signalHandler($signal) {
		switch ($signal) {
			case SIGCHLD:
				echo 'SIGCHLD';
				break;
			case SIGQUIT:
				echo 'SIGQUIT';
				break;
			case SIGUSR1:
				echo 'SIGUSR1';
				break;
		}
	}
}
?>