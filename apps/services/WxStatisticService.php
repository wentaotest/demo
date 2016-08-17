<?php namespace June\apps\services;

/**
 * 微信公众号统计服务类
 * 
 * 微信统计功能接口仅向第三方平台开发者开放，在微信开放平台接入公众号登录授权即可成为第三方平台开发者
 * 
 * @author 安佰胜
 */
class WxStatisticService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxStatisticService();
		}

		return self::$_inst;
	}
	
	public function getUserSummary() {
		
	}
	
	public function getUserCumulate() {
		
	}
	
	public function getArticleSummary() {
		
	}
	
	public function getArticleTotal() {
		
	}
	
	public function getUserRead() {
		
	}
	
	public function getUserReadHour() {
		
	}
	
	public function getUserShare() {
		
	}
	
	public function getUserShareHour() {
		
	}
	
	public function getUpstreamMsg() {
		
	}
	
	public function getUpstreamMsgHour() {
		
	}
	
	public function getUpstreamMsgWeek() {
		
	}
	
	public function getUpstreamMsgMonth() {
		
	}
	
	public function getUpstreamMsgDist() {
		
	}
	
	public function getUpstreamMsgDistWeek() {
		
	}
	
	public function getUpstreamMsgDistMonth() {
		
	}
	
	public function getInterfaceSummary() {
		
	}
	
	public function getInterfaceSummaryHour() {
		
	}
}
?>