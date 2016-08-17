<?php namespace June\apps\models;

use June\Core\Model\MysqlModel;
use June\Core\Model;
use June\Core\DbConn\MysqlDbConn;
use June\Core\JuneException;

/**
 * 夺宝记录Model类 mysql
 *
 * 数据元定义
 * {
 * 		....
 * }
 */
class MWxAccessTokens extends MysqlModel {
	
	/**
	 * Model类的构造函数
	 *
	 * @param MysqlDbConn $db_conn
	 * @param string $tc_name
	 *
	 * @author 王文韬
	 */
	public function __construct($db_conn, $tc_name) {
		parent::__construct($db_conn, $tc_name);
	
		$this->_insert_stmt = $this->_pdo->prepare("INSERT INTO `$tc_name` (`access_token`, `update_ts`, `expire_ts`) VALUES (?, ?, ?)");
	}
	
	/**
	 * {@inheritDoc}
	 * @see \June\Core\Model\MysqlModel::insert()
	 */
	public function insert($values) {
		// 输入数据的内务处理（Housekeeping）
		$data = array_values($values);
		
		// 调用父级方法
		parent::insert($data);
	}

}