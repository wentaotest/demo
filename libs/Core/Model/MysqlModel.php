<?php namespace June\Core\Model;

use June\Core\Model;
use June\Core\JuneException;
use June\Core\DbConn\MysqlDbConn;
use June\Core\QueryBuilder\MysqlQueryBuilder;

/**
 * MySQL的Model父类
 *
 * @author 安佰胜
 */
class MysqlModel extends Model {
	
	/**
	 * PDO实例
	 * 
	 * @var \PDO $_pdo
	 */
	protected $_pdo = null;
	
	/**
	 * 插入语句Statement
	 * 
	 * @var \PDOStatement $_insert_stmt
	 */
	protected $_insert_stmt = null;
	
	
	/**
	 * 查询语句Statement
	 * 
	 * @var \PDOStatement $_select_stmt
	 */
	protected $_select_stmt = null;
	
	/**
	 * 更新语句Statement
	 *
	 * @var \PDOStatement $_update_stmt
	 */
	protected $_update_stmt = null;
	
	/**
	 * 删除语句Statement
	 * 
	 * @var \PDOStatement $_delete_stmt
	 */
	protected $_delete_stmt = null;
	
	/**
	 * 查询器对象实例
	 * 
	 * @var MysqlQueryBuilder
	 */
	protected $_query_inst = null;
	
	/**
	 * 构造函数
	 * 
	 * @param MysqlDbConn $db_conn_inst
	 * @param string $tc_name
	 * 
	 * @author 安佰胜
	 */
	public function __construct($db_conn_inst, $tc_name) {
		$this->_db_conn_inst = $db_conn_inst;
		$this->_tc_name      = $tc_name;
		$this->_pdo          = $db_conn_inst->getPdo();
	}
	
	/**
	 * 插入数据操作
	 * 
	 * {@inheritDoc}
	 * @see \June\Core\Model::insert()
	 */
	public function insert($values) {
		// 用户输入数据的处理（Housekeeping-内务处理），由子类覆写该方法
		
		// 执行数据插入，确保插入的是索引数组，形如：array(1, 2, 3)
		return $this->_doInsert(array_values($values));
	}
	
	/**
	 * 执行数据插入
	 * 
	 * @param array $data
	 * @return integer $row_id
	 * 
	 * @author 安佰胜
	 */
	protected function _doInsert(array $data) {
		try {
			$this->_insert_stmt->execute($data);
		} catch (\PDOException $e) {
			throw new JuneException($e->getMessage());
		}
		
		return $this->_pdo->lastInsertId();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::find()
	 * 
	 * @author 安佰胜
	 */
	public function find($criteria, $fields = array(), $sort_by = array(), $skip = NULL, $limit = NULL) {
		// 使用查询语句构造器
		$this->_query_inst = new MysqlQueryBuilder($this->getTcName());
		
		// 将MongoDB的查询方式转化为MySQL查询
		$this->_convertCriterias($criteria);
		
		// 编译MySQL查询语句
		if (!empty($fields)) {
			$this->_query_inst->fields($fields);
		}
		
		if (!empty($sort_by)) {
			$this->_query_inst->sort($sort_by);
		}
		
		if (!empty($skip)) {
			$this->_query_inst->skip($skip);
		}
		
		if (!empty($limit)) {
			$this->_query_inst->limit($limit);
		}
		
		$compiled_sql = $this->_query_inst->from($this->getTcName())->compile();
		
		$this->_select_stmt = $compiled_sql;
		
		return $this->_doSelect();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::findOne()
	 * 
	 * @author 安佰胜
	 */
	public function findOne($criteria, $fields = array()) {
		$res = $this->find($criteria, $fields, array(), 0, 1);
		
		return !empty($res) && is_array($res) ? $res[0] : false;
	}
	
	/**
	 * 执行查询操作
	 * 
	 * @return array|boolean
	 * 
	 * @author 安佰胜
	 */
	protected function _doSelect() {
		try {
			$res = $this->_pdo->query($this->_select_stmt)->fetchAll(\PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			$msg = $e->getMessage() . "MySQL select failed ! SQL statement is: " . $this->_select_stmt;
			throw new JuneException($msg, $e->getCode());
		}
		
		return $res;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::update()
	 * 
	 * @author 安佰胜
	 */
	public function update($criteria, $data, $options = array()) {
		// 使用查询语句构造器
		$this->_query_inst = new MysqlQueryBuilder($this->getTcName());
		
		$this->_convertCriterias($criteria);
		
		$table_nm = $this->getTcName();
		
		$update_stmt = "UPDATE `$table_nm` SET";
		if (!empty($data) && is_array($data)) {
			foreach ($data as $col_nm => $value) {
				if (is_string($value)) {
					$update_stmt .= " `$col_nm` = `$value`,";
				} else {
					$update_stmt .= " `$col_nm` = $value,";
				}
			}
		}
		$compiled_sql = trim($update_stmt, ',') . ' WHERE ' .$this->_query_inst->getCompiledWheres();
		
		$this->_update_stmt = $compiled_sql;
		
		return $this->_doUpdate();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::findAndModify()
	 * 
	 * @author 安佰胜
	 */
	public function findAndModify($criteria, $data = array(), $fields = array(), $options = array()) {
		
	}
	
	/**
	 * 执行更新操作
	 * 
	 * @return mixed
	 * 
	 * @author 安佰胜
	 */
	protected function _doUpdate() {
		return $this->_pdo->exec($this->_update_stmt);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::delete()
	 * 
	 * @author 安佰胜
	 */
	public function delete($criteria, $erase = false, $options = array()) {
		// 使用查询语句构造器
		$this->_query_inst = new MysqlQueryBuilder($this->getTcName());
		
		$this->_convertCriterias($criteria);
		
		$table_nm = $this->getTcName();
		
		if ($erase) {
			$delete_stmt = "DELETE FROM `$table_nm` WHERE ";
		} else {
			$delete_stmt = "UPDATE `$table_nm` SET `enable` = false WHERE ";
		}
		
		$compiled_sql = $delete_stmt . $this->_query_inst->getCompiledWheres();
		
		$this->_delete_stmt = $compiled_sql;
		
		return $this->_doDelete();
	}
	
	/**
	 * 执行删除操作
	 * 
	 * @return mixed
	 * 
	 * @author 安佰胜
	 */
	protected function _doDelete() {
		return $this->_pdo->exec($this->_delete_stmt);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::drop()
	 * 
	 * @author 安佰胜
	 */
	public function drop() {
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::increase()
	 * 
	 * @author 安佰胜
	 */
	public function increase($criteria, $field, $var = 1, $extra = array(), $options = array()) {
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::decrease()
	 * 
	 * @author 安佰胜
	 */
	public function decrease($criteria, $field, $val = -1, $extra = array(), $options = array()) {
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::getTcInfo()
	 * @todo 暂未实现该功能
	 * 
	 * @author 安佰胜
	 */
	public function getTcInfo() {
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::getTcName()
	 */
	public function getTcName() {
		return $this->_tc_name;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::createIndex()
	 * 
	 * @author 安佰胜
	 */
	public function createIndex($keys, $options) {
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::deleteIndex()
	 * 
	 * @author 安佰胜
	 */
	public function deleteIndex($keys) {
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model::deleteIndexes()
	 * 
	 * @author 安佰胜
	 */
	public function deleteIndexes() {
		
	}
	
	/**
	 * 将MongoDB查询语句转化为MySQL查询器
	 * 
	 * @param array $criteria
	 * @param string $logic_op
	 * 
	 * @author 安佰胜
	 */
	private function _convertCriterias(array $criteria, $logic_op = 'AND') {
		
		if (!empty($criteria)) {
			foreach ($criteria as $k => $v) {
				if ($k == '$or' || $k == '$and') {
					$op = $k == '$or' ? 'OR' : 'AND';
					
					// 递归调用
					$this->_convertCriterias($v, $op);
				}
			}
			
			foreach ($criteria as $k => $v) {
				$func_nm = $logic_op == 'OR' ? 'orWhere' : 'where';
					
				if (is_array($v)) {
				
					foreach ($v as $c_k => $c_v) {
						switch ($c_k) {
							case '$gt':
								$this->_query_inst->$func_nm($k, '>', $c_v);
								break;
							case '$gte':
								$this->_query_inst->$func_nm($k, '>=', $c_v);
								break;
							case '$lt':
								$this->_query_inst->$func_nm($k, '<', $c_v);
								break;
							case '$lte':
								$this->_query_inst->$func_nm($k, '<=', $c_v);
								break;
							case '$ne':
								$this->_query_inst->$func_nm($k, '!=', $c_v);
								break;
						}
					}
				
				} else {
					$this->_query_inst->$func_nm($k, '=', $v);
				}
			}
			
			// 执行where编译（对or条件加括号）
			$this->_query_inst->compileWheres();
		}
		
		return;
	}

}