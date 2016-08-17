<?php namespace June\apps;

use June\Core\DbConn\MongoDbConn;
use June\Core\DbConn\MysqlDbConn;
use June\Core\Model\MongoModel;

/**
 * 应用数据库连接
 * 
 * 注意：这里连接的是该应用使用的所有数据库的连接
 *
 * @author 安佰胜
 */
class AppsDbConnPool {
	protected static $_inst;
	protected $_mongo_conn_inst;
	protected $_mysql_conn_inst;
	
	/**
	 * 构造函数
	 *
	 * @author 安佰胜
	 */
	protected function __construct() {
		// 默认使用mongodb数据库
		$this->useDbServer('mongodb');
	}
	
	/**
	 * 数据库连接类实例
	 *
	 * @author 安佰胜
	 */
	public static function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new static();
		}
	
		return self::$_inst;
	}
	
	/**
	 * 使用哪种数据库（支持同时使用多种数据库）
	 * 
	 * @param string $db_type
	 * 
	 * @author 安佰胜
	 */
	public function useDbServer($db_type) {
		switch ($db_type) {
			case 'mongodb':
				$this->_mongo_conn_inst = MongoDbConn::getInstance(C('db_conf', 'mongodb'));
				break;
			case 'mysql':
				$this->_mysql_conn_inst = MysqlDbConn::getInstance(C('db_conf', 'mysql'));
				break;
		}
	}
	
	/**
	 * 获取MongoDB数据库连接类的实例
	 * 
	 * @return MongoDbConn
	 * 
	 * @author 安佰胜
	 */
	public function getMongoDbConnInst() {
		return MongoDbConn::getInstance(C('db_conf', 'mongodb'));
	}
	
	/**
	 * 关闭MongoDb数据库连接
	 * 
	 * @author Vincent An
	 */
	public function closeMongoDbConn() {
		MongoDbConn::disconnect();
	}
	
	/**
	 * 获取MySQL数据库连接类的实例
	 * 
	 * @return MysqlDbConn
	 * 
	 * @author 安佰胜
	 */
	public function getMySqlDBConnInst() {
		return MysqlDbConn::getInstance(C('db_conf', 'mysql'));
	}
	
	/**
	 * 关闭MySQLDb数据库连接
	 * 
	 * @author Vincent An
	 */
	public function closeMySqlDbConn() {
		MysqlDbConn::disconnect();
	}
	
	/**
	 * 获取集合的Model实例（仅限mongodb，为了兼容旧的逻辑代码）
	 * 
	 * @param string $coll_name
	 * @return MongoModel
	 * 
	 * @author 安佰胜
	 */
	public function getModel($coll_name) {
		return $this->_mongo_conn_inst->getModel($coll_name);
	}
	
	
}

?>