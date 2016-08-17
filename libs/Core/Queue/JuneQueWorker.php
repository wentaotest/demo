<?php namespace June\Core\Queue;

use June\Core\JuneException;
use June\Core\Queue\JuneQueue;

class JuneQueWorker {
	
	/**
	 * 消费者(worker)从消息队列中获取任务
	 * 
	 * @param JuneQueue $queue 消息队列实例
	 * @param integer $timeout 等待延时，默认为0-表示立即获取
	 * @return array|false     如果失败则返回false，否则返回包含任务编号和任务内容的数组
	 */
	public function reserve(JuneQueue $queue, $timeout = 0) {
		if (empty($queue)) {
			$message = 'No Queue found while reserving job !';
			throw new JuneException($message);
		}
		
		if (isset($timeout) && !empty($timeout)) {
			$queue->write(sprintf('reserve-with-timeout %d', $timeout));
		} else {
			$queue->write('reserve');
		}
		
		$status = strtok($queue->read(), ' ');

		switch ($status) {
			// 已被消费者获取，任务正在被处理
			case 'RESERVED':
				return array(
					'id' => (integer) strtok(' '),
					'body' => $queue->read((integer) strtok(' '))
				);
			
			// 队列中的任务达到超时临界或已经超时
			case 'DEADLINE_SOON':
			case 'TIMED_OUT':
			default:
				$queue->error($status);
				return false;
		}
	}
	
	/**
	 * 消费者(worker)从消息队列中移除任务
	 * 
	 * @param JuneQueue $queue 消息队列实例
	 * @param string $job_id   任务编号
	 * @throws JuneException   异常
	 * @return boolean         是否删除成功
	 * 
	 * @author 安佰胜
	 */
	public function delete(JuneQueue $queue, $job_id) {
		if (empty($queue)) {
			$message = 'No Queue found while deleting job !';
			throw new JuneException($message);
		}
		
		if (empty($job_id)) {
			$message = 'No job_id supplied while deleting job !';
			throw new JuneException($message);
		}
		
		$queue->write(sprintf('delete %d', $job_id));
		$status = $queue->read();
		
		switch ($status) {
			// 成功删除
			case 'DELETED':
				return true;
			// 未找到
			case 'NOT_FOUND':
			default:
				$queue->error($status);
				return false;
		}
	}
	
	/**
	 * 将已被消费者获取的任务再次添加到就绪(ready)队列(queue)
	 *
	 * @param JuneQueue $queue 消息队列实例
	 * @param integer $job_id  任务编号
	 * @param integer $pri     任务优先级
	 * @param integer $delay   延迟进入就绪队列的时间，此时任务的状态为delayed
	 * @return boolean         是否成功
	 * 
	 * @author 安佰胜
	 */
	public function release(JuneQueue $queue, $job_id, $pri = 0, $delay = 0) {
		if (empty($queue)) {
			$message = 'No Queue found while deleting job !';
			throw new JuneException($message);
		}
		
		if (empty($job_id)) {
			$message = 'No job_id supplied while deleting job !';
			throw new JuneException($message);
		}
		
		$queue->write(sprintf('release %d %d %d', $job_id, $pri, $delay));
		$status = $queue->read();
	
		switch ($status) {
			case 'RELEASED':
			case 'BURIED':
				return true;
			case 'NOT_FOUND':
			default:
				$queue->error($status);
				return false;
		}
	}
	
	/**
	 * 将任务休眠(buried)直至被再次唤醒(kick)，休眠状态的任务被添加到一个FIFO的列表中，不会消失也不会被执行
	 *
	 * @param JuneQueue $queue 消息队列实例
	 * @param integer $job_id  任务编号
	 * @param integer $pri     重新分配的运行优先级
	 * @return boolean         返回结果
	 */
	public function bury(JuneQueue $queue, $job_id, $pri) {
		if (empty($queue)) {
			$message = 'No Queue found while deleting job !';
			throw new JuneException($message);
		}
		
		if (empty($job_id)) {
			$message = 'No job_id supplied while deleting job !';
			throw new JuneException($message);
		}
		
		$queue->write(sprintf('bury %d %d', $job_id, $pri));
		$status = $queue->read();
	
		switch ($status) {
			case 'BURIED':
				return true;
			case 'NOT_FOUND':
			default:
				$queue->error($status);
				return false;
		}
	}
	   
	/**
	 * 运行任务(job)
	 * 
	 * @param array $job_data 任务数据如下：
	 * 		- id        任务编号
	 * 		- body      任务内容，json字符串
	 * @return boolean
	 * 
	 * @author 安佰胜
	 */
	public function execute($job_data) {
		if (empty($job_data['id']) || empty($job_data['body'])) {
			$message = 'Job data is Invalid !';
			throw new JuneException($message);
		}
		
		$job_body = json_decode($job_data['body'], true);
		$job_class = "June\\apps\\queue_jobs\\" . $job_body['job_class'];
		
		$job_instance = new $job_class($job_body['args']);
		
		$job_instance->beforePerform();
		$job_instance->perform($job_data['id']);
		$job_instance->afterPerform();
	}
}
