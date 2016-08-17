<?php namespace June\Core\DbConn;

use June\Core\DbConn;
use June\Core\JuneException;
use June\Core\Model\MongoModel;

/**
 * MongoDB数据库连接类定义
 *
 * @author 安佰胜
 */
class MongoDbConn extends DbConn {
	
	/**
	 * MongoDBConn连接类的构造函数
	 * 
	 * @param array $dsn
	 * 
	 * @author 安佰胜
	 */
    protected function __construct($dsn) {
        $this->_dsn = $dsn;
    }
    
    /**
     * 连接MongoDb数据库
     * 
     * 延迟建立连接的目的：一方面是为了减少不必要的数据库连接，另一方面是为了实现读写分离
     * 
     * @param boolean $master 是否【希望】连接主节点（连接到哪个节点最终还是要取决于配置信息）
     * 
     * @author 安佰胜
     */
    public function connect($master = true) {
    	$class_mongo = "\MongoClient";
    	if(!class_exists($class_mongo)){
    		$class_mongo = '\Mongo';
    	}
    	
    	// 连接类型
    	if ($master) {
    		$conn_type = 'master';
    	} else {
    		$conn_type = 'slave';
    	}
    	
    	// 连接属性名称及数据库属性名称
    	$conn_param = '_' . $conn_type . '_db_serv_conn';
    	$db_param   = '_' . $conn_type . '_db';
    	
    	// 判断是否已经建立连接，如果已建立则直接返回
    	if (!empty($this->$conn_param) && !empty($this->$db_param)) {
    		return $this;
    	}
    	
    	// 只有开启读写分离时，才会建立从节点连接
    	if ($this->_dsn['mode'] == 2 && $master == false) {
    		// 用户ip地址加请求时间（秒）组合字符串作为哈希计算的参数变量，那么，同一用户在一秒钟内永远连接同一台数据库服务器
    		$key = get_client_ip() . date('YmdHis', time());
    		
    		$host_info = $this->_getHostByHash($key);
    	} else {
    		$host_info = $this->_dsn['master'];
    	}
    	
    	try {
    		$db_name = $this->_dsn['db_name'];
    		$fs_name = $this->_dsn['fs_name'];
    		$this->$conn_param = new $class_mongo($host_info['server'], $host_info['options']);
    		$this->$db_param = $this->$conn_param->$db_name;
    	} catch (JuneException $e) {
    		error_log($e->__toString());
    	}
    	
    	return $this;
    }
    
    /**
     * 关闭mongodb所有连接
     * 
     * @author Vincent An
     */
    public static function disconnect() {
    	$db_conn_inst = self::$_inst;
    	
    	$conn_types = array();
    	
    	if (isset($db_conn_inst->_master_db_serv_conn)) {
    		$conn_types[] = $db_conn_inst->_master_db_serv_conn;
    	}
    	
    	if (isset($db_conn_inst->_slave_db_serv_conn)) {
    		$conn_types[] = $db_conn_inst->_slave_db_serv_conn;
    	}
    	
    	$res = true;
    	
    	if (!empty($conn_types)) {
    		foreach ($conn_types as $t) {
    			// 获取所有连接
    			$connections = $t->getConnections();
    			 
    			// 关闭所有连接
    			if (!empty($connections)) {
    				foreach ($connections as $conn) {
    					$res = $t->close($conn['hash']);
    					
    					if (!$res) {
    						continue;
    					}
    				}
    			}
    		}
    	}	
    	
    	return $res;
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
    	$slaves_cnt = count($this->_dsn['slaves']);
    
    	$ret = array();
    	if ($slaves_cnt < 1) {
    		$ret['server']  = $this->_dsn['master']['server'];
    		$ret['options'] = $this->_dsn['master']['options'];
    	} elseif ($slaves_cnt < 2) {
    		$ret['server']  = $this->_dsn['slaves'][0]['server'];
    		$ret['options'] = $this->_dsn['slaves'][0]['options'];
    	} else {
    		$u = strtolower($key);
    		$id = sprintf("%u", crc32($key));
    		 
    		$m = base_convert(intval(fmod($id, $slaves_cnt)), 10, $slaves_cnt);
    		$idx = $m{0};
    		 
    		$ret['server']  = $this->_dsn['slaves'][$idx]['server'];
    		$ret['options'] = $this->_dsn['slaves'][$idx]['options'];
    	}
    
    	return $ret;
    }
    
    
    /**
     * 获取数据库服务器连接
     * 
     * @param boolean $master 是否连接到主库
     * @return \MongoClient
     * 
     * @author 安佰胜
     */
    public function getDbServConn($master = true) {
    	// 连接类型
    	if ($master) {
    		$conn_type = 'master';
    	} else {
    		$conn_type = 'slave';
    	}
    	
    	$conn_param = '_' . $conn_type . '_db_serv_conn';
    	
    	if (empty($this->$conn_param)) {
    		$this->connect($master);
    	} 
    	
        return $this->$conn_param;
    }
    
    /**
     * 获取数据库的连接
     * 
     * @param boolean $master 是否连接到主库
     * @return \MongoClient
     * 
     * @author 安佰胜
     */
    public function getDb($master = true) {
    	// 连接类型
    	if ($master) {
    		$conn_type = 'master';
    	} else {
    		$conn_type = 'slave';
    	}
    	
    	$db_param   = '_' . $conn_type . '_db';
    	
    	if (empty($this->$db_param)) {
    		$this->connect($master);
    	}
    	
        return $this->$db_param;
    }
    
    /**
     * 获取集合或表的model实例
     * 
     * @return MongoModel
     * 
     * @author 安佰胜
     */
    public function getModel($coll_name) {
    	try {
    		if (!isset($this->_models[$coll_name])) {
    			$crumbs = explode('_', $coll_name);
    
    			$model_name = "";
    			foreach ($crumbs as $c) {
    				$model_name .= ucfirst($c);
    			}
    
    			$model_class = "June\\apps\\models\\".'M'.$model_name;
    			if (!class_exists($model_class)) {
    				throw new \Exception('Class "' . $model_class . '" Not Exist!');
    			}
    			
    			// 如果有Model对象的getter方法，则直接调用，否则自行创建对应Model实例
    			$getter_method = 'getM' . $model_name;
    			
    			$rc = new \ReflectionClass($model_class);
    			if ($rc->hasMethod($getter_method)) {
    				$this->$getter_method();
    			} else {
    				$this->_models[$coll_name] = new $model_class($this, $coll_name);
    			}
    		}
    	} catch (Exception $e) {
    		error_log($e->__toString());
    	}
    
    	return $this->_models[$coll_name];
    }
} 
?>