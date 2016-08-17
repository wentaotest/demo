<?php namespace June\apps\services;

use June\Core\JuneException;
class FastDfsService {
	static private $_inst;
	
	private $_config = array();
	
	private $_server;
	
	private $_tracker;
	
	private $_tracker_list;
	
	private $_storage;
	
	private $_storage_list;
	
	private function __construct($config) {
		if (!empty($config) && is_array($config)) {
			$this->_config = $config;
		}
		
		$this->connectToTracker();
	}

	static public function getInstance($config=array()) {
		if (empty(self::$_inst)) {
			self::$_inst = new FastDfsService($config);
		}

		return self::$_inst;
	}
	
	/**
	 * 获取FastDFS客户端版本号
	 * 
	 * @author 安佰胜
	 */
	public function getClientVersion() {
		return fastdfs_client_version();
	}
	
	/**
	 * 连接所有的Tracker
	 * 
	 * @author 安佰胜
	 */
	public function connectToAllTrackers() {
		$res = fastdfs_tracker_make_all_connections();
		
		if ($res) {
			$this->_tracker_list = astdfs_tracker_list_groups();
		}
		
		return $res;
	}
	
	/**
	 * 关闭所有的Tracker
	 * 
	 * @author 安佰胜
	 */
	public function closeAllTrackers() {
		$res = fastdfs_tracker_close_all_connections();
		
		if ($res) {
			unset($this->_tracker_list);
		}
		
		return $res;
	}
	
	/**
	 * 获得一个Tracker连接
	 * 
	 * @author 安佰胜
	 */
	public function connectToTracker() {
		$this->_tracker = fastdfs_tracker_get_connection();
		
		if (!fastdfs_active_test($this->_tracker)) {
			throw new JuneException(fastdfs_get_last_error_info(), fastdfs_get_last_error_no());
		}
		
		return $this->_tracker;
	}
	
	/**
	 * 建立服务器连接
	 * 
	 * @author 安佰胜
	 */
	public function connectToServer($ip_addr, $port) {
		$this->_server = fastdfs_connect_server($ip_addr, $port);
		
		return $this->_server;
	}
	
	/**
	 * 断开服务器连接
	 * 
	 * @author 安佰胜
	 */
	public function disconnToServer() {
		return fastdfs_disconnect_server($this->_server);
	}
	
	/**
	 * 获得一个Storage连接
	 * 
	 * @author 安佰胜
	 */
	public function connectToStorage() {
		$this->_storage = fastdfs_tracker_query_storage_store();
		
		if (!$this->_storage) {
			throw new JuneException(fastdfs_get_last_error_info(), fastdfs_get_last_error_no());
		}
	}
	
	/**
	 * 获取Tracker
	 * 
	 * @author 安佰胜
	 */
	public function getTracker() {
		return $this->_tracker;
	}
	
	/**
	 * 获取Storage
	 * 
	 * @author 安佰胜
	 */
	public function getStorage() {
		return $this->_storage;
	}
	
	/**
	 * 上传一主多从文件到FastDFS中
	 * 
	 * @param array $files 一主多从文件数组，如: array('source' => 'xxx', 'sm' => 'xxx', 'xs' => 'xx')
	 * @return string $fullname
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	public function uploadMultiFiles($files) {
		$source = $files['source'];
		unset($files['source']);
		
		/**
		 * array fastdfs_storage_upload_by_filename(
		 * 		string local_filename
		 * 		[, 
		 *      string file_ext_name, 
		 *      array meta_list, 
		 *      string group_name, 
		 *      array tracker_server, 
		 *      array storage_server
		 *      ]
		 * )
		 */
		$file_info = fastdfs_storage_upload_by_filename($source, null, array(), null, $this->_tracker, $this->_storage);
		
		if ($file_info) {
			unlink($source);
			
			$group_nm = $file_info['group_name'];
			$file_nm  = $file_info['filename'];
			
			foreach ($files as $size => $path) {
				fastdfs_storage_upload_slave_by_filename($path, $group_nm, $file_nm, '_' . $size);
				unlink($path);
			}
			
			return '/' . $group_nm . '/' . $file_nm;
		} else {
			throw new JuneException(fastdfs_get_last_error_info(), fastdfs_get_last_error_no());
		}
	}
	
	/**
	 * 上传文件到FastDFS中
	 * 
	 * @param string $file
	 * @param string $ext_nm
	 * @return return $fullname
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	public function uploadFile($file, $ext_nm) {
		$file_info = fastdfs_storage_upload_by_filename($file, $ext_nm);
		
		if ($file_info) {
			// unlink($file);
				
			$group_nm = $file_info['group_name'];
			$file_nm  = $file_info['filename'];
				
			return '/' . $group_nm . '/' . $file_nm;
		} else {
			throw new JuneException(fastdfs_get_last_error_info(), fastdfs_get_last_error_no());
		}
	}
}

?>