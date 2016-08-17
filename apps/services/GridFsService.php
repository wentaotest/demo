<?php namespace June\apps\services;

use June\Core\JuneException;
class GridFsService {
	static private $_inst;

	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new GridFsService();
		}

		return self::$_inst;
	}
	
	/**
	 * 获取GridFS的连接
	 * 
	 * @param boolean $master
	 * @return MongoGridFS
	 * 
	 * @author 安佰胜
	 */
	private function _getFS($master = true) {
		if ($master) {
			$conn_name = '_master_db_serv_conn';
		} else {
			$conn_name = '_slave_db_serv_conn';
		}
		
		$db_conn = $this->_db->connect($master)->$conn_name;
		
		$fs = $db_conn->selectDB(C('db_conf', 'mongodb.fs_name'))->getGridFS();
		
		return $fs;
	}
	
	/**
	 * 将文件保存到GridFS中
	 * 
	 * @param string $filepath
	 * @param array $metadata
	 * @param array $options
	 * @return MongoId
	 * 
	 * @author 安佰胜
	 */
	public function saveFile($filepath, $metadata = array(), $options = array()) {
		$fs = $this->_getFS(true);
		
		$id = $fs->storeFile($filepath, $metadata, $options);
		
		return $id;
	}
	
	/**
	 * 将字节串保存到GridFS中
	 * 
	 * @param string $bytes
	 * @param array $metadata
	 * @param array $options
	 * 
	 * @author 安佰胜
	 */
	public function saveBytes($bytes, $metadata = array(), $options = array()) {
		$fs = $this->_getFS(true);
		
		$id = $fs->storeBytes($bytes, $metadata, $options);
		
		return $id;
	}
	
	/**
	 * 将通过form上传的文件保存到GridFS中
	 * 
	 * @param string $upload_file_name 表单中文件上传input框的name值
	 * @param array $metadata
	 * 
	 * @author 安佰胜
	 */
	public function saveUpload($upload_file_name, $metadata = array()) {
		$fs = $this->_getFS(true);
		
		$id = $fs->storeUpload($bytes, $metadata, $options);
		
		return $id;
	}
	
	/**
	 * 获取所有符合查询条件的文件记录
	 * 
	 * @param array $query 查询条件
	 * @param array $fields 返回字段
	 * @return MongoGridFSCursor
	 * 
	 * @author 安佰胜
	 */
	public function find($query, $fields = array()) {
		return $this->_getFS(false)->find($query, $fields);
	}
	
	/**
	 * 获取符合查询条件的单条文件记录
	 * 
	 * @param mixed $query
	 * @param mixed $fields
	 * @return MongoGridFSFile
	 * 
	 * @author 安佰胜
	 */
	public function findOne($query, $fields = array()) {
		return $this->_getFS(false)->findOne($query, $fields);
	}
	
	/**
	 * 通过id获取文件对象
	 * 
	 * @param MongoId $fid
	 * @return MongoGridfsFile
	 * 
	 * @author 安佰胜
	 */
	public function getFileById($fid) {
		return $this->_getFS(false)->get($fid);
	}
	
	/**
	 * 获取文件字节流
	 * 
	 * @param mixed $query
	 * @throws JuneException
	 * @return string $bytes
	 * 
	 * @author 安佰胜
	 */
	public function getBytes($query) {
		if ($query instanceof \MongoId) {
			$file = $this->getFileById($query);
		} else {
			$file = $this->findOne($query);
		}
		
		if ($file) {
			return $file->getBytes();
		} else {
			throw new JuneException("查询文件未找到！");
		}
	}
	
	/**
	 * 获取文件名称
	 * 
	 * @param mixed $query
	 * @throws JuneException
	 * @return string $filename
	 * 
	 * @author 安佰胜
	 */
	public function getFilename($query) {
		if ($query instanceof \MongoId) {
			$file = $this->getFileById($query);
		} else {
			$file = $this->findOne($query);
		}
		
		if ($file) {
			return $file->getFilename();
		} else {
			throw new JuneException("查询文件未找到！");
		}
	}
	
	/**
	 * 获取文件资源句柄
	 * 
	 * @param mixed $query
	 * @throws JuneException
	 * @return resource $resource
	 * 
	 * @author 安佰胜
	 */
	public function getResource($query) {
		if ($query instanceof \MongoId) {
			$file = $this->getFileById($query);
		} else {
			$file = $this->findOne($query);
		}
		
		if ($file) {
			return $file->getResource();
		} else {
			throw new JuneException("查询文件未找到！");
		}
	}
	
	/**
	 * 获取文件大小
	 * 
	 * @param mixed $query
	 * @throws JuneException
	 * @return integer $size
	 * 
	 * @author 安佰胜
	 */
	public function getSize($query) {
		if ($query instanceof \MongoId) {
			$file = $this->getFileById($query);
		} else {
			$file = $this->findOne($query);
		}
		
		if ($file) {
			return $file->getSize();
		} else {
			throw new JuneException("查询文件未找到！");
		}
	}
	
	/**
	 * 将文件从GridFS中输出到文件系统
	 * 
	 * @param mixed $query
	 * @param string $filename
	 * @throws JuneException
	 * @return integer
	 * 
	 * @author 安佰胜
	 */
	public function write($query, $filename) {
		if ($query instanceof \MongoId) {
			$file = $this->getFileById($query);
		} else {
			$file = $this->findOne($query);
		}
		
		if ($file) {
			return $file->write($filename);
		} else {
			throw new JuneException("查询文件未找到！");
		}
	}

	/**
	 * 真正删除条件匹配的数据
	 * 
	 * @param array $criteria
	 * @return integer
	 * 
	 * @author 王索
	 */
	public function remove($criteria) {
		$res = $this->_getFS(false)->remove($criteria);
        return ((int) $res['ok'] == 1);
	}
}