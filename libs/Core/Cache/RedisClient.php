<?php namespace June\Core\Cache;

use June\Core\Cache;
use June\Core\JuneException;
/**
 * 缓存规则如下：
 * 
 * 1、统计类缓存，如无严格要求，该类缓存可定时更新，不必实时
 * 2、队列类缓存，新数据添加时即添加入队列缓存，数据更新时需要同步更新缓存
 * 3、结果类缓存，针对热点访问数据缓存结果，持续访问会延长缓存生存周期，但一定次数访问后必须与数据进行一致性验证
 *
 * @author 安佰胜
 */

class RedisClient extends Cache {
	static private $_inst;
	private $_cfg;
	private $_m_host;
	private $_m_port;
	private $_s_host;
	private $_s_port;
	private $_db_id = 0;
	private $_redis = array('w' => null, 'r' => null);
	private $_is_persistent = false;
	
	private function __construct($cfg) {
		$this->_cfg = $cfg;
	}
	
	static public function getInstance($cfg) {
		if (empty(self::$_inst)) {
			self::$_inst = new RedisClient($cfg);
		}
		
		return self::$_inst;
	}
	
	/**
	 * 建立redis缓存连接
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::connect()
	 */
	public function connect() {
		$m_host = !empty($this->_cfg['master']['host']) ? $this->_cfg['master']['host'] : null;
		$m_port = !empty($this->_cfg['master']['port']) ? $this->_cfg['master']['port'] : null;
		
		/**
		 * 考虑到可能使用读写分离，所以使用延迟建立连接，那么本方法会选择两个连接配置信息：读服务器和写服务器
		 * 待执行到增删改查时，再建立真正的连接
		 */
		if ($this->_cfg['mode'] == 1) {
			// 用户ip地址加请求时间（分钟）组合字符串作为哈希计算的参数变量，那么，同一用户在一分钟内永远连接同一台缓存服务器
			$key = get_client_ip() . date('YmdHi', time());
			
			$res = $this->_getHostByHash($key);
			
			$s_host = $res['host'];
			$s_port = $res['port'];
		} else {
			$s_host = $m_host;
			$s_port = $m_port;
		}
		
		if (empty($m_host) || empty($m_port) || empty($s_host) || empty($s_port)) {
			throw new JuneException('Redis缓存配置信息不完整！');
		} else {
			$this->_m_host = $m_host;
			$this->_m_port = $m_port;
			$this->_s_host = $s_host;
			$this->_s_port = $s_port;
		}
		
		return $this;
	}
	
	/**
	 * 建立redis缓存持久连接
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::pconnect()
	 */
	public function pconnect() {
		$this->_is_persistent = true;
	}
	
	/**
	 * 使用哈希算法实现集群负载均衡
	 * 
	 * @param string $key
	 * @return array $host_info
	 * 
	 * @author 安佰胜
	 */
	private function _getHostByHash($key) {
		$slaves_cnt = count($cfg['slaves']);
		
		$ret = array();
		if ($slaves_cnt < 1) {
			$ret['host'] = $cfg['master']['host'];
			$ret['port'] = $cfg['master']['port'];
		} elseif ($slaves_cnt < 2) {
			$ret['host'] = $cfg['slaves'][0]['host'];
			$ret['port'] = $cfg['slaves'][0]['port'];
		} else {
			$u = strtolower($key);
        	$id = sprintf("%u", crc32($key));
         
        	$m = base_convert(intval(fmod($id, $slaves_cnt)), 10, $slaves_cnt);
        	$idx = $m{0};
        	
        	$ret['host'] = $cfg['slaves'][$idx]['host'];
        	$ret['port'] = $cfg['slaves'][$idx]['port'];
		}
		
		return $ret;
	}
	
	/**
	 * 执行建立连接操作
	 * 
	 * @param string $rw_mode 读写模式：w-写、r-读
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _doConnect($rw_mode = 'w') {
		if ($rw_mode == 'w') {
			$host = $this->_m_host;
			$port = $this->_m_port;
		} else {
			$host = $this->_s_host;
			$port = $this->_s_port;
		}
		
		// 连接redis
		if ($this->_is_persistent) {
			$res = $this->_redis[$rw_mode]->pconnect($host, $port);
		} else {
			$res = $this->_redis[$rw_mode]->connect($host, $port);
		}
		
		// 切换数据库，不同数据库中相同的键名可以对应不同的键值
		$this->_redis[$rw_mode]->select($this->_db_id);
			
		if (empty($res)) {
			throw new JuneException('Redis连接失败，请检查redis服务是否正常运行！');
		}
	}
	
	/**
	 * 获取一个Redis类的实例
	 * 
	 * @param string $rw_mode
	 * @return object \Redis类的实例
	 * 
	 * @author 安佰胜
	 */
	public function getRedis($rw_mode = 'w') {
		if (empty($this->_redis[$rw_mode])) {
			$this->_redis[$rw_mode] = new \Redis();
			
			$this->_doConnect($rw_mode);
		}
		
		return $this->_redis[$rw_mode];
	}
	
	/**
	 * 关闭redis缓存的连接
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::close()
	 */
	public function close($tag = 'all') {
		switch ($tag) {
			case 'all':
				$this->_redis['w']->close();
				$this->_redis['r']->close();
				break;
			case 'master':
				$this->_redis['w']->close();
				break;
			case 'slave':
				$this->_redis['r']->close();
				break;
		}
	}
	
	/**
	 * 切换数据库
	 * 
	 * @param integer $db_id
	 * @return RedisClient $obj
	 * 
	 * @author 安佰胜
	 */
	public function selectDB($db_id) {
		$this->_db_id = $db_id;
		
		return $this;
	}
	
	/**
	 * 返回当前使用的数据库编号
	 * 
	 * @return integer $db_id
	 * 
	 * @author 安佰胜
	 */
	public function getDBId() {
		return $this->_db_id;
	}
	
	/**
	 * 查看缓存记录是否存在
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::exists()
	 */
	public function exists($key) {
		if (empty($key)) {
			throw new \JuneException('Redis键名不能为空！');
		}
		
		$redis = $this->getRedis('r');
		
		return $redis->exists($key);
	}
	
	/**
	 * 设置redis缓存记录的键值，键名不存在则插入，键名存在则覆写
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::set()
	 */
	public function set($key, $val, $timeout = null) {
		if (empty($key)) {
			throw new \JuneException('Redis键名不能为空！');
		}
		
		$redis = $this->getRedis('w');
		
		// 将要存储的数据进行序列化（转成json虽然体积只是序列化结果的一半多一些，但反序列化效率差了一个数量级）
		$s_val = serialize($val);
		
		$res = $redis->set($key, $s_val);
		
		if (!empty($timeout)) {
			$redis->expire($key, $timeout);
		}
		
		return $res;
	}
	
	/**
	 * 按照键名获取键值
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::get()
	 */
	public function get($key) {
		if (empty($key)) {
			throw new \JuneException('Redis键名不能为空！');
		}
		
		$redis = $this->getRedis('r');
		
		$res = $redis->get($key);
		
		if (!empty($res)) {
			$ret = unserialize($res);
			
			// 如果
		} else {
			$ret = $res;
		}
		
		return $ret;
	}
	
	/**
	 * 缓存记录键值自增
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::increment()
	 */
	public function increment($key, $inc_val = 1) {
		if (empty($key)) {
			throw new \JuneException('Redis键名不能为空！');
		}
		
		$redis = $this->getRedis('w');
		
		return $redis->incr($key, $inc_val);
	}
	
	/**
	 * 缓存记录键值自减
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::decrement()
	 */
	public function decrement($key, $dec_val = 1) {
		if (empty($key)) {
			throw new \JuneException('Redis键名不能为空！');
		}
		
		$redis = $this->getRedis('w');
		
		return $redis->incr($key, $dec_val);
	}
	
	/**
	 * 删除缓存记录
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::delete()
	 */
	public function delete($key) {
		if (empty($key)) {
			throw new \JuneException('Redis键名不能为空！');
		}
		
		$redis = $this->getRedis('w');
		
		return $redis->delete($key);
	}
	
	/**
	 * 批量删除缓存记录
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::batchDelete()
	 */
	public function batchDelete($keys) {
		if (!is_array($keys) || empty($keys)) {
			throw new \JuneException('键名列表必须是数组且不能为空！');
		}
		
		$redis = $this->getRedis('w');
		
		return $redis->delete($keys);
	}
	
	/**
	 * 清空所有缓存记录
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Cache::flush()
	 */
	public function flush() {
		$redis = $this->getRedis('w');
		
		return $redis->flushAll();
	}
}
?>