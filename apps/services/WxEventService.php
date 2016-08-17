<?php namespace June\apps\services;

/**
 * 微信公众号事件响应服务类
 *
 * @author 安佰胜
 */
class WxEventService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxEventService();
		}

		return self::$_inst;
	}
	
	/**
	 * 微信事件处理
	 *
	 * @param object $event
	 * @return mixed $handle_result
	 *
	 * @author 安佰胜
	 */
	public function handler($event) {
		$event_type = $event->Event;
	
		switch ($event_type) {
			
			// 订阅事件（包括未关注公众号扫描二维码时的事件）
			case 'subscribe':
				$res = $this->_subscribeEventHandler($event);
				break;
				
			// 取消订阅事件
			case 'unsubscribe':
				$res = $this->_unsubscribEventHandler($event);
				break;
				
			// 点击菜单拉取消息时的事件
			case 'CLICK':
				$res = $this->_clickEventHandler($event);
				break;
				
			// 点击菜单跳转链接时的事件
			case 'VIEW':
				$res = $this->_viewEventHandler($event);
				break;
				
			// 已关注公众号扫描二维码时的事件
			case 'SCAN':
				$res = $this->_scanEventHandler($event);
				break;
				
			// 上报地理位置时的事件
			case 'LOCATION':
				$res = $this->_locationEventHandler($event);
				break;
				
			// 点击菜单扫码扫码推送的事件（微信5.4以上）
			case 'scancode_push':
				$res = $this->_scancodePushEventHandler($event);
				break;
				
			// 点击菜单扫码扫码推送（且弹出“消息接收中”提示框）的事件（微信5.4以上）
			case 'scancode_waitmsg':
				$res = $this->_scancodeWaitmsgEventHandler($event);
				break;
				
			// 点击菜单弹出系统拍照发图的事件（微信5.4以上）
			case 'pic_sysphoto':
				$res = $this->_picSysphotoEventHandler($event);
				break;
				
			// 点击菜单弹出拍照或者相册发图的事件（微信5.4以上）
			case 'pic_photo_or_album':
				$res = $this->_picPhotoOrAlbumEventHandler($event);
				break;
				
			// 点击菜单弹出微信相册发图器的事件（微信5.4以上）
			case 'pic_weixin':
				$res = $this->_picWeixinEventHandler($event);
				break;
				
			// 点击菜单弹出地理位置选择器的事件（微信5.4以上）
			case 'location_select':
				$res = $this->_locationSelectEventHandler($event);
				break;
		}
		
		// 向用户发送事件响应消息
		WxMessageService::getInstance()->responseEvent($event);
	
		return $res;
	}
	
	/**
	 * 关注事件处理函数
	 *
	 * @param object $event 事件对象
	 * @return mixed $is_saved 是否成功保存用户信息
	 *
	 * @author 安佰胜
	 */
	private function _subscribeEventHandler($event) {
		// 获取用户的open_id
		$user_open_id = trim($event->FromUserName);
		
		$user_data = array();
		$user_data['status'] = 1; // 已关注
		$user_data['qr_scene_id'] = isset($event->EventKey) ? trim($event->EventKey) : null; // qrscene_为前缀，后面是二维码的参数值
		$user_data['qr_ticket']   = isset($event->Ticket) ? trim($event->Ticket) : null; // 二维码的ticket，可以用来换取二维码图片
	
		// 保存到数据库
		$criteria = array('openid' => $user_open_id);
		$options = array('upsert' => true);
	
		$m_wx_users = $this->_db->getMWxUsers();
	
		return $m_wx_users->findAndModify($criteria, array('$set' => $user_data), array(), $options);
	}
	
	/**
	 * 取消关注事件处理函数
	 *
	 * @param object $event 事件对象
	 * @return mixed $is_updated 是否成功更新用户信息
	 *
	 * @author 安佰胜
	 */
	private function _unsubscribEventHandler($event) {
		// 获取用户的open_id
		$user_open_id = trim($event->FromUserName);
	
		$criteria = array('openid' => $user_open_id);
		$data = array('status' => 0); // 取消关注
	
		$m_wx_users = $this->_db->getMWxUsers();
	
		return $m_wx_users->findAndModify($criteria, array('$set' => $data));
	}
	
	private function _clickEventHandler($event) {
	
	}
	
	private function _viewEventHandler($event) {
	
	}
	
	/**
	 * 扫描二维码事件处理函数
	 * 
	 * @param object $event
	 * 
	 * @author 安佰胜
	 */
	private function _scanEventHandler($event) {
		$data = array('openid'  => trim($event->FromUserName),
					  'action'  => trim($event->Event),
					  'ts'      => new \MongoDate(trim($event->CreateTime)),
					  'content' => array('qr_scene_id' => trim($event->EventKey),
					  					 'qr_ticket'   => trim($event->Ticket),
					  ),  
		);
		
		return $this->_db->getMWxUserActions()->insertOneGetId($data);
	}
	
	/**
	 * 上报地理位置事件处理函数
	 * 
	 * @param object $event
	 * 
	 * @author 安佰胜
	 */
	private function _locationEventHandler($event) {
		// 获取用户的open_id
		$user_open_id = trim($event->FromUserName);
		
		$user_data = array();
		$user_data['location'] = array('lat' => trim($event->Latitude), 'lon' => trim($event->Longitude));
		$user_data['loc_precision'] = trim($event->Precision);
		$user_data['update_ts'] = new \MongoDate(time());
		
		// 更新用户当前位置
		$criteria = array('openid' => $user_open_id);
		$options = array('upsert' => true);
		
		$user_info = $this->_db->getMWxUsers()->findAndModify($criteria, array('$set' => $user_data), array(), $options);
		
		// 保存用户轨迹信息
		$track_data = array('open_id' => $user_open_id,
							'location' => array('lat' => $event->Latitude, 'lon' => $event->Longitude),
							'loc_precision' => $event->Precision,
							'ts' => new \MongoDate(time()),
		);
		
		$this->_db->getMWxUserTracks()->insert($track_data);
		
		return $user_info;
	}
	
	private function _scancodePushEventHandler($event) {
		$data = array('openid'  => trim($event->FromUserName),
					  'action'  => trim($event->Event),
					  'ts'      => new \MongoDate(trim($event->CreateTime)),
					  'content' => array('event_key' => trim($event->EventKey),
								         'scan_code_info' => array('scan_type'   => trim($event->ScanCodeInfo->ScanType),
								         						   'scan_result' => trim($event->ScanCodeInfo->ScanResult))
					  ),
		);
		
		// TODO:可添加相应的业务逻辑
		
		$res = $this->_db->getMWxUserActions()->insertOneGetId($data);
		
		return $res;
	}
	
	private function _scancodeWaitmsgEventHandler($event) {
		$data = array('openid'  => trim($event->FromUserName),
				      'action'  => trim($event->Event),
				      'ts'      => new \MongoDate(trim($event->CreateTime)),
				      'content' => array('event_key' => trim($event->EventKey),
						                 'scan_code_info' => array('scan_type'   => trim($event->ScanCodeInfo->ScanType),
								                                   'scan_result' => trim($event->ScanCodeInfo->ScanResult))
				      ),
		);
		
		// TODO:可添加相应的业务逻辑
		
		$res = $this->_db->getMWxUserActions()->insertOneGetId($data);
		
		return $res;
	}
	
	private function _picSysphotoEventHandler($event) {
		$data = array('openid'  => trim($event->FromUserName),
				      'action'  => trim($event->Event),
				      'ts'      => new \MongoDate(trim($event->CreateTime)),
				      'content' => array('event_key' => trim($event->EventKey),
						                 'send_pics_info' => array('count'   => trim($event->SendPicsInfo->Count),
								                                   'pic_list' => $event->SendPicsInfo->PicList)
				      ),
		);
		
		// TODO:可添加相应的业务逻辑
		
		$res = $this->_db->getMWxUserActions()->insertOneGetId($data);
		
		return $res;
	}
	
	private function _picPhotoOrAlbumEventHandler($event) {
		$data = array('openid'  => trim($event->FromUserName),
					  'action'  => trim($event->Event),
					  'ts'      => new \MongoDate(trim($event->CreateTime)),
				      'content' => array('event_key' => trim($event->EventKey),
						                 'send_pics_info' => array('count'   => trim($event->SendPicsInfo->Count),
								                                   'pic_list' => $event->SendPicsInfo->PicList)
				      ),
		);
		
		// TODO:可添加相应的业务逻辑
		
		$res = $this->_db->getMWxUserActions()->insertOneGetId($data);
		
		return $res;
	}
	
	private function _picWeixinEventHandler($event) {
		$data = array('openid'  => trim($event->FromUserName),
				      'action'  => trim($event->Event),
				      'ts'      => new \MongoDate(trim($event->CreateTime)),
				      'content' => array('event_key' => trim($event->EventKey),
						                 'send_pics_info' => array('count'   => trim($event->SendPicsInfo->Count),
								                                   'pic_list' => $event->SendPicsInfo->PicList)
				      ),
		);
		
		// TODO:可添加相应的业务逻辑
		
		$res = $this->_db->getMWxUserActions()->insertOneGetId($data);
		
		return $res;
	}
	
	private function _locationSelectEventHandler($event) {
		$data = array('openid'  => trim($event->FromUserName),
				      'action'  => trim($event->Event),
				      'ts'      => new \MongoDate(trim($event->CreateTime)),
				      'content' => array('event_key' => trim($event->EventKey),
						                 'send_location_info' => array('lat'   => trim($event->SendLocationInfo->Location_X),//待确定是经度还是纬度
						                 		                       'lon'   => trim($event->SendLocationInfo->Location_Y),//待确定是经度还是纬度
						                 		                       'scale' => trim($event->SendLocationInfo->Scale),
						                 							   'label' => trim($event->SendLocationInfo->Label),
						                 							   'poiname' => trim($event->SendLocationInfo->Poiname),
						                 )
				      ),
		);
		
		// TODO:可添加相应的业务逻辑
		
		$res = $this->_db->getMWxUserActions()->insertOneGetId($data);
		
		return $res;
	}
}
?>