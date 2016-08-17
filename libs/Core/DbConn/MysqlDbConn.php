<?php namespace June\Core\DbConn;

use June\Core\DbConn;
use June\Core\JuneException;

/**
 * MySQL数据库连接类定义
 * 
 * 注意：这里使用了PDO通用数据库接口，PDO提供了三个预定义类：PDO、PDOStatement和PDOException（预定义异常，可自动捕获）
 *
 * @author 安佰胜
 */
class MysqlDbConn extends DbConn {
	
	/**
	 * 数据库配置项
	 * 
	 * @var array
	 */
	private $_opt = array();
	
	/**
	 * 数据库服务器连接PDO实例
	 * 
	 * @var \PDO $_pdo
	 */
	private $_pdo = null;
	
	/**
	 * MysqlDBConn连接类的构造函数
	 * 
	 * @param array $opt 连接参数
	 * 
	 * @author 安佰胜
	 */
    protected function __construct($opt) {
        $this->_opt = $opt;
    }
    
    /**
     * 获取MySQL数据库连接类的单例
     *
     * 注意：单例的子类如果不使用数组各自存储，会造成覆盖或无法得到另一个子类的实例
     *
     * @param array $opt
     * @return \June\Core\DbConn
     *
     * @author 安佰胜
     */
    public static function getInstance($opt) {
    
    	if (empty(self::$_inst['my_db_conn_inst'])) {
    		self::$_inst['my_db_conn_inst'] = new static($opt);
    	}
    	
    	if (empty(self::$_inst['my_db_conn_inst']->_pdo)) {
    		self::$_inst['my_db_conn_inst']->connect();
    	}
    	
    	try {
    		$pdo_info = self::$_inst['my_db_conn_inst']->getPdoInfo();
    	} catch (JuneException $e) {
    		self::$_inst['my_db_conn_inst']->connect();
    	}
    
    	return self::$_inst['my_db_conn_inst'];
    }
    
    /**
     * 使用PDO连接到MySql服务器
     * 
     * @todo 支持一主多从，读写分离
     * 
     * @author 安佰胜
     */
    public function connect() {
    	// 单个MySQL数据库
    	$opt = $this->_opt['master'];
    	$opt['dbname'] = $this->_opt['dbname'];
    	
    	$host = !empty($opt['host']) ? $opt['host'] : '127.0.0.1';
    	$port = !empty($opt['port']) ? $opt['port'] : 3306;
    	
//     	$driver_options = $opt['is_persistent'] ? array(\PDO::ATTR_PERSISTENT => true) : array();
    	$driver_options = array(\PDO::ATTR_PERSISTENT => false); // 不使用长连接 By Vincent An 20160729
    	
    	if (empty($opt['dbname'])) {
    		$message = '"dbname" should not be empty value! ';
    		throw new JuneException($message);
    	}
    	
    	if (empty($opt['user']) || empty($opt['pwd'])) {
    		$message = '"user" or "pwd" should not be empty value! ';
    		throw new JuneException($message);
    	}
    	
    	$dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $opt['dbname'];
    	
    	// 使用PDO连接MySQL数据库
    	try {
    		$this->_pdo = new \PDO($dsn, $opt['user'], $opt['pwd'], $driver_options);
    	} catch (\PDOException $e) {
    		throw new JuneException($e->getMessage());
    	}
    	
    	return $this;
    }
    
    /**
     * 关闭MySQL连接
     * 
     * @author Vincent An
     */
    public static function disconnect() {
    	$db_conn_inst = isset(self::$_inst['my_db_conn_inst']) ? self::$_inst['my_db_conn_inst'] : null;
    	
    	if (isset($db_conn_inst->_pdo)) {
    		$db_conn_inst->_pdo = null;
    		//unset(self::$_inst['my_db_conn_inst']);
    	}
    }
    
    /**
     * 移除PDO连接
     * 
     * @author Vincent An
     */
    public function removePdo() {
    	$this->_pdo = null;
    }
    
    /**
     * 返回PDO对象
     * 
     * @return \PDO
     * 
     * @author 安佰胜
     */
    public function getPdo() {
    	return $this->_pdo;
    }
    
    /**
     * 获取PDO连接信息（主要是供后台任务使用，检查连接是否超时）
     * 
     * return mixed 如果是连接断掉的错误，结果会是类似“MySQL server has gone away”或者“Lost connection to MySQL server”的字串，或者是false
     * 
     * @author Vincent An
     */
    public function getPdoInfo() {
    	error_reporting(E_ERROR | E_WARNING | E_PARSE);
    	
    	set_error_handler('June\\Core\\DbConn\\MysqlDbConn::pdo_error_handler');
    	
    	$pdo_info = $this->_pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
    	
    	set_error_handler('catch_bck_cli_php_error');
    	
    	return $pdo_info;
    }
    
    /**
     * PDO错误捕获
     * 
     * @param integer $error_lv
     * @param string $error_msg
     * @param string $filename
     * @param string $line
     * @param string $symbols
     * @throws JuneException
     */
    static public function pdo_error_handler($error_lv, $error_msg, $filename, $line, $symbols) {
    	throw new JuneException('PDO连接失败！原因：' . $error_msg, JuneException::CONN_ERR_MYSQL_DB);
    }
    
    /**
     * 开启事务
     * 
     * 注意：
     * 		事务过程中的MySQL数据库连接必须是同一个！！！
     * 
     * @throws JuneException
     * 
     * @author 安佰胜
     */
    public function beginTransaction() {
    	try {
    		// 首先，如果有未提交的事务，先强制执行一下提交操作
    		if ($this->inTransaction()) {
    			$this->_pdo->commit();
    		}
    		
    		// 关闭自动提交
    		$this->_pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
    		
    		// 开启异常处理
    		$this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    		
    		// 启动事务处理
    		$this->_pdo->beginTransaction();
    		
    	} catch (\PDOException $e) {
    		throw new JuneException($e->getMessage());
    	}
    }
    
    /**
     * 检查当前是否处于事务中
     * 
     * @return boolean
     * 
     * @author 安佰胜
     */
    public function inTransaction() {
    	return $this->_pdo->inTransaction();
    }
    
    /**
     * 使用MySQL事务处理业务（通常是调用Model中的成员方法）
     * 
     * 注意：MySQL的默认存储引擎MyISAM并不支持事务，需要切换为InnoDB存储引擎！！！
     * 
     * @param string $callback 回调方法
     * @param object $obj 回调方法所在的对象
     * @param string|array $params 回调方法的参数
     * @throws JuneException
     * @return \MysqlDbConn
     * 
     * @author 安佰胜
     */
    public function transaction($callback, $obj, $params = null) {
    	if (!$this->_pdo->inTransaction()) {
    		$this->beginTransaction();
    	}
    	
    	if (empty($callback) || !is_object($obj)) {
    		$message = "Invalid Parameters! ";
    		throw new JuneException($message);
    	}
    	
    	try {
    		if (empty($params)) {
    			call_user_method($method_name, $obj);
    		} else {
    			call_user_method($method_name, $obj, $params);
    		}
    	} catch (\Exception $e) {
    		throw new JuneException($e->getMessage());
    	}
    	
    	return $this;
    }
    
    /**
     * 执行事务提交
     * 
     * @param string $rollback 事务回调函数或方法名称
     * @param string|array $roll_params 回调函数或方法的参数
     * @param string $class_nm 回调方法所在的类名
     * 
     * @author 安佰胜
     */
    public function commitTransaction($rollback_func = null, $roll_params = null, $class_nm = null) {
    	try {
    		// 执行事务提交操作
    		if ($this->inTransaction()) {
    			$this->_pdo->commit();
    		}
    	} catch (\PDOException $e) {
    		// 执行事务回滚操作
    		$this->rollbackTransaction($rollback_func, $roll_params, $class_nm);
    	}
    	
    	// 恢复自动提交
    	$this->_pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
    	
    	// 关闭异常处理（无需关闭）
    	//$this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
    	
    }
    
    /**
     * 执行事务回滚
     * 
     * @param mixed $rollback_func 事务回调函数或方法名称
     * @param string|array $roll_params 回调函数或方法的参数
     * @param string $class_nm 回调方法所在的类名
     * 
     * 注意：
     * 		如果回调函数是某个类的成员方法，则该成员方法必须为静态方法！！！
     * 
     * @author Vincent An
     */
    public function rollbackTransaction($rollback_func = null, $roll_params = null, $class_nm = null) {
    	try {
    		// 执行事务回滚操作
    		$this->_pdo->rollBack();
    		
    		// 恢复自动提交
    		$this->_pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
    		 
    		// 执行回滚回调函数
    		if ($rollback_func) {
    			if ($class_nm) {
    				$rollback = array($class_nm, $rollback_func);
    				
    				if (empty($roll_params)) {
    					call_user_func($rollback);
    				} else {
    					call_user_func($rollback, $roll_params);
    				}
    			} else {
    				if (empty($roll_params)) {
    					call_user_func($rollback_func);
    				} else {
    					call_user_func($rollback_func, $roll_params);
    				}
    			}
    		}
    	} catch (\PDOException $e) {
    		throw new JuneException('事务回滚失败！原因：' . $e->getMessage());
    	}
    }
    
    /**
     * 原生-查找多条数据
     * 
     * @param string $sql SQL语句
     * @param integer $fetch_style 控制下一行如何返回给调用者
     * @return array|boolean 查找结果，失败均为false
     * 
     * @author 安佰胜
     */
    public function rawFind($sql, $fetch_style = \PDO::FETCH_BOTH) {
    	return $this->_pdo->query($sql)->fetchAll($fetch_style);
    }
    
    /**
     * 原生-查找单条数据
     * 
     * @param string $sql SQL语句
     * @param integer $fetch_style 控制下一行如何返回给调用者
     * @param integer $cursor_orientation 对于 一个 PDOStatement 对象表示的可滚动游标，该值决定了哪一行将被返回给调用者
     * @param integer $cursor_offset 根据 $cursor_orientation 参数的值，此参数有不同的意义
     * @return array|boolean 查找结果，失败均为false
     * 
     * @author 安佰胜
     */
    public function rawFindOne($sql, $fetch_style= \PDO::FETCH_ASSOC, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
    	return $this->_pdo->query($sql)->fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }
    
    /**
     * 原生-更新数据
     * 
     * @param string $sql SQL语句
     * @param array $input 一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组
     * @return integer 影响行数
     * 
     * @author 安佰胜
     */
    public function rawUpdate($sql, array $input = array()) {
    	return $this->rawSql($sql, $input);
    }
    
    /**
     * 原生-插入数据
     * 
     * @param string $sql SQL语句
     * @param array $input 一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组
     * @return integer 影响行数
     * 
     * @author 安佰胜
     */
    public function rawInsert($sql, array $input = array()) {
    	return $this->rawSql($sql, $input);
    }
    
    /**
     * 原生-删除数据
     * 
     * @param string $sql SQL语句
     * @param array $input 一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组
     * @return integer 影响行数
     * 
     * @author 安佰胜
     */
    public function rawDelete($sql, array $input = array()) {
    	return $this->rawSql($sql, $input);
    }
    
    /**
     * 原生-执行诸如：INSERT、UPDATE、DELETE等原生操作
     * 
     * @param string $sql SQL语句
     * @param array $input 一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组
     * @throws JuneException
     * @return integer 影响行数
     * 
     * @author 安佰胜
     */
    public function rawSql($sql, array $input = array()) {
    	try {
    		$update = $this->_pdo->prepare($sql);
    		$update->execute($input);
    	} catch (\PDOException $e) {
    		throw new JuneException($e->getMessage());
    	}
    	 
    	return $update->rowCount();
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\DbConn::getModel()
     * @todo 暂无必要实现该函数
     */
    public function getModel($tc_name) {
    	
    }
    
    
  
}