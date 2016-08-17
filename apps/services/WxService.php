<?php namespace June\apps\services;

use June\Core\JuneException;

class WxService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_mysql_db_conn();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxService();
		}

		return self::$_inst;
	}

	/**
	 * 验证微信签名
	 *
	 * @author 王文韬 2016-08-16
	 */
	public function checkSignature() {
		$signature = xg('signature');
		$timestamp = xg('timestamp');
		$nonce     = xg('nonce');

		$token = C('wx_conf', 'token');
		$tmp_arr = array($token, $timestamp, $nonce);
		sort($tmp_arr, SORT_STRING);
		$tmp_str = implode($tmp_arr);
		$tmp_str = sha1($tmp_str);

		return $tmp_str == $signature;
	}

	/**
	 * 获取access_tokens(暂时未保存至数据库)
	 *
	 * @author 王文韬 2016-08-16
	 */
	public function getAccessToken() {
		$time = time();
		$sql = "SELECT * FROM `wx_access_tokens` WHERE `id` = 1 AND `expire_ts` > $time LIMIT 1";
		$info = $this->_db->rawFindOne($sql);
		if (!$info) {
			$appid      = C('wx_conf', 'app_id');
			$app_secret = C('wx_conf', 'app_secret');
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$app_secret;

			$res = file_get_contents($url);
			$result = json_decode($res, true);
			if (!empty($result['access_token'])) {
				$expire_ts = $time + $result['expires_in'];
				$sql = "INSERT INTO `wx_access_tokens` (id, access_token, update_ts, expire_ts) VALUES (1, '{$result['access_token']}', {$time}, {$expire_ts}) ON DUPLICATE KEY UPDATE access_token = '{$result['access_token']}', update_ts = {$time},  expire_ts={$expire_ts}";
				
				if ($this->_db->rawInsert($sql)) {
					$result = array('access_token' => $result['access_token'], 'errmsg' => '', 'errcode' => 0);
				} else {
					$result = array('access_token' => '', 'errmsg' => 'insert into mysql faled', 'errcode' => 1);
				}
			} else {
				$result = array('access_token' => '', 'errmsg' => '', 'errcode' => $result['errcode']);
			}
		} else {
			$result = array('access_token' => $info['access_token'], 'errmsg' => '', 'errcode' => 0);
		}
		
		return $result;
	}

	/**
	 * 获取微信服务器地址列表
	 *
	 * @return array ip地址列表
	 * 
	 * @author 王文韬 2016-08-17
	 */
	public function getWxServerIp() {
		$result = $this->getAccessToken();
		$access_token = $result['access_token']; 
		$url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$access_token;
		$res = file_get_contents($url);
		$res = json_decode($res, true);

		return $res['ip_list'];
	}
} 