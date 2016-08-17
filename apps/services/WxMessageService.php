<?php namespace June\apps\services;

use June\apps\services\WxManagerService;
use June\Core\JuneException;
/**
 * 微信公众号消息服务类
 * 
 * 消息分为以下几种类型：
 * 
 * 1、被动回复消息
 * 2、客服消息
 * 3、群发消息
 * 4、模板消息
 * 
 * @author 安佰胜
 */

class WxMessageService {
	static private $_inst;
	private $_db;
	private $_msg_tpl = array(
			'text'  => "<xml>
					    <ToUserName><![CDATA[%s]]></ToUserName>
			            <FromUserName><![CDATA[%s]]></FromUserName>
			            <CreateTime>%d</CreateTime>
			            <MsgType><![CDATA[text]]></MsgType>
			            <Content><![CDATA[%s]]></Content>
			            </xml>",
			'image' => "<xml>
			            <ToUserName><![CDATA[%s]]></ToUserName>
			            <FromUserName><![CDATA[%s]]></FromUserName>
			            <CreateTime>%d</CreateTime>
			            <MsgType><![CDATA[image]]></MsgType>
			            <Image><MediaId><![CDATA[media_id]]></MediaId></Image>
			            </xml>",
			'voice' => "<xml>
			            <ToUserName><![CDATA[%s]]></ToUserName>
			            <FromUserName><![CDATA[%s]]></FromUserName>
			            <CreateTime>%d</CreateTime>
			            <MsgType><![CDATA[voice]]></MsgType>
			            <Voice><MediaId><![CDATA[media_id]]></MediaId></Voice>
			            </xml>",
			'video' => "<xml>
			            <ToUserName><![CDATA[%s]]></ToUserName>
			            <FromUserName><![CDATA[%s]]></FromUserName>
			            <CreateTime>%d</CreateTime>
			            <MsgType><![CDATA[video]]></MsgType>
			            <Video>
						<MediaId><![CDATA[media_id]]></MediaId>
			            <Title><![CDATA[title]]></Title>
			            <Description><![CDATA[description]]></Description>
			            </Video>
			            </xml>",
			'music' => "<xml>
			            <ToUserName><![CDATA[%s]]></ToUserName>
			            <FromUserName><![CDATA[%s]]></FromUserName>
			            <CreateTime>%d</CreateTime>
			            <MsgType><![CDATA[music]]></MsgType>
			            <Music>
						<Title><![CDATA[TITLE]]></Title>
						<Description><![CDATA[DESCRIPTION]]></Description>
						<MusicUrl><![CDATA[MUSIC_Url]]></MusicUrl>
						<HQMusicUrl><![CDATA[HQ_MUSIC_Url]]></HQMusicUrl>
						<ThumbMediaId><![CDATA[media_id]]></ThumbMediaId>
						</Music>
						</xml>",
			'news'  => "<xml>
					    <ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%d</CreateTime>
						<MsgType><![CDATA[news]]></MsgType>
						<ArticleCount>2</ArticleCount>
						<Articles>
						<item>
						<Title><![CDATA[title1]]></Title> 
						<Description><![CDATA[description1]]></Description>
						<PicUrl><![CDATA[picurl]]></PicUrl>
						<Url><![CDATA[url]]></Url>
						</item>
						<item>
						<Title><![CDATA[title]]></Title>
						<Description><![CDATA[description]]></Description>
						<PicUrl><![CDATA[picurl]]></PicUrl>
						<Url><![CDATA[url]]></Url>
						</item>
						</Articles>
						</xml> ",
	);

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxMessageService();
		}

		return self::$_inst;
	}
	
	/**
	 * （被动）回复普通消息
	 * 
	 * @param object $message
	 * 
	 * @author 安佰胜
	 */
	public function response($message) {
		$type = trim($message->MsgType);

		switch ($type) {
			case 'text':
				$this->_responseText($message);
				break;
			case 'image':
				$this->_responseImage($message);
				break;
			case 'voice':
				$this->_responseVoice($message);
				break;
			case 'video':
				$this->_responseVideo($message);
				break;
			case 'shortvideo':
				$this->_responseShortVideo($message);
				break;
			case 'location':
				$this->_responseLocation($message);
				break;
			case 'link':
				$this->_responseLink($message);
				break;
		}
	}
	
	/**
	 * 回复普通文本消息
	 * 
	 * @param object $msg
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _responseText($msg) {
		if (empty($msg)) {
			throw new JuneException('微信消息内容不能为空！');
		}
		
		$msg_data = array('to'        => trim($msg->ToUserName),
						  'from'      => trim($msg->FromUserName),
						  'create_ts' => trim($msg->CreateTime),
						  'msg_type'  => trim($msg->MsgType),
						  'content'   => array('text' => trim($msg->Content)),
						  'msg_id'    => trim($msg->MsgId),
		);
		
		$res = $this->_db->getMWxMessages()->insert($msg_data);
		
		if ($res) {
			$content = "您发送的消息类型为：" . $msg_data['msg_type'] . "，内容为：" . $msg_data['content']['text'];
			
			echo sprintf($this->_msg_tpl['text'], $msg_data['from'], $msg_data['to'], $msg_data['create_ts'], $content);
		} else {
			throw new JuneException('消息保存失败！');
		}
	}
	
	/**
	 * 回复图片消息
	 * 
	 * @param object $msg
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _responseImage($msg) {
		if (empty($msg)) {
			throw new JuneException('微信消息内容不能为空！');
		}
		
		$msg_data = array('to'        => trim($msg->ToUserName),
						  'from'      => trim($msg->FromUserName),
						  'create_ts' => trim($msg->CreateTime),
						  'msg_type'  => trim($msg->MsgType),
						  'content'   => array('pic_url' => trim($msg->PicUrl), 'media_id' => trim($msg->MediaId)),
						  'msg_id'    => trim($msg->MsgId),
		);
		
		$res = $this->_db->getMWxMessages()->insert($msg_data);
		
		if ($res) {
			$content = "您发送的消息类型为：" . $msg_data['msg_type'] . "，内容为：" . $msg_data['content']['pic_url'];

			echo sprintf($this->_msg_tpl['text'], $msg_data['from'], $msg_data['to'], $msg_data['create_ts'], $content);
		} else {
			throw new JuneException('消息保存失败！');
		}
	}
	
	/**
	 * 回复语音消息
	 * 
	 * @param object $msg
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _responseVoice($msg) {
		if (empty($msg)) {
			throw new JuneException('微信消息内容不能为空！');
		}
		
		$msg_data = array('to'        => trim($msg->ToUserName),
				          'from'      => trim($msg->FromUserName),
				          'create_ts' => trim($msg->CreateTime),
				          'msg_type'  => trim($msg->MsgType),
				          'content'   => array('format' => trim($msg->Format), 'media_id' => trim($msg->MediaId)),
				          'msg_id'    => trim($msg->MsgId),
		);
		
		$res = $this->_db->getMWxMessages()->insert($msg_data);
		
		if ($res) {
			$content = "您发送的消息类型为：" . $msg_data['msg_type'] . "，内容为：" . $msg_data['content']['format'];

			echo sprintf($this->_msg_tpl['text'], $msg_data['from'], $msg_data['to'], $msg_data['create_ts'], $content);
		} else {
			throw new JuneException('消息保存失败！');
		}
	}
	
	/**
	 * 回复视频消息
	 * 
	 * @param object $msg
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _responseVideo($msg) {
		if (empty($msg)) {
			throw new JuneException('微信消息内容不能为空！');
		}
	
		$msg_data = array('to'        => trim($msg->ToUserName),
				          'from'      => trim($msg->FromUserName),
				          'create_ts' => trim($msg->CreateTime),
				          'msg_type'  => trim($msg->MsgType),
				          'content'   => array('thumb_media_id' => trim($msg->ThumbMediaId), 'media_id' => trim($msg->MediaId)),
				          'msg_id'    => trim($msg->MsgId),
		);
	
		$res = $this->_db->getMWxMessages()->insert($msg_data);
	
		if ($res) {
			$content = "您发送的消息类型为：" . $msg_data['msg_type'] . "，内容为：" . $msg_data['content']['thumb_media_id'];

			echo sprintf($this->_msg_tpl['text'], $msg_data['from'], $msg_data['to'], $msg_data['create_ts'], $content);
		} else {
			throw new JuneException('消息保存失败！');
		}
	}
	
	/**
	 * 回复小视频消息
	 * 
	 * @param object $msg
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _responseShortVideo($msg) {
		if (empty($msg)) {
			throw new JuneException('微信消息内容不能为空！');
		}
		
		$msg_data = array('to'        => trim($msg->ToUserName),
				          'from'      => trim($msg->FromUserName),
				          'create_ts' => trim($msg->CreateTime),
				          'msg_type'  => trim($msg->MsgType),
				          'content'   => array('thumb_media_id' => trim($msg->ThumbMediaId), 'media_id' => trim($msg->MediaId)),
				          'msg_id'    => trim($msg->MsgId),
		);
		
		$res = $this->_db->getMWxMessages()->insert($msg_data);
		
		if ($res) {
			$content = "您发送的消息类型为：" . $msg_data['msg_type'] . "，内容为：" . $msg_data['content']['thumb_media_id'];

			echo sprintf($this->_msg_tpl['text'], $msg_data['from'], $msg_data['to'], $msg_data['create_ts'], $content);
		} else {
			throw new JuneException('消息保存失败！');
		}
	}
	
	/**
	 * 回复地理位置消息
	 * 
	 * @param object $msg
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _responseLocation($msg) {
		if (empty($msg)) {
			throw new JuneException('微信消息内容不能为空！');
		}
		
		$msg_data = array('to'        => trim($msg->ToUserName),
				          'from'      => trim($msg->FromUserName),
				          'create_ts' => trim($msg->CreateTime),
				          'msg_type'  => trim($msg->MsgType),
				          'content'   => array('location' => array('lat' => trim($msg->Location_X),
				          										   'lon' => trim($msg->Location_Y)
				                                             ), 
				          					   'scale' => trim($msg->Scale),
				          					   'label' => trim($msg->Label),
				          				 ),
				          'msg_id'    => trim($msg->MsgId),
		);
		
		$res = $this->_db->getMWxMessages()->insert($msg_data);
		
		if ($res) {
			$content = "您发送的消息类型为：" . $msg_data['msg_type'] . "，内容为：" . $msg_data['content']['label'];

			echo sprintf($this->_msg_tpl['text'], $msg_data['from'], $msg_data['to'], $msg_data['create_ts'], $content);
		} else {
			throw new JuneException('消息保存失败！');
		}
	}
	
	/**
	 * 回复链接消息
	 * 
	 * @param object $msg
	 * @throws JuneException
	 * 
	 * @author 安佰胜
	 */
	private function _responseLink($msg) {
		if (empty($msg)) {
			throw new JuneException('微信消息内容不能为空！');
		}
		
		$msg_data = array('to'        => trim($msg->ToUserName),
				          'from'      => trim($msg->FromUserName),
				          'create_ts' => trim($msg->CreateTime),
				          'msg_type'  => trim($msg->MsgType),
				          'content'   => array('title' => trim($msg->Title), 
				          					   'description' => trim($msg->Description),
				          					   'url' => trim($msg->Url),
				                         ),
				          'msg_id'    => trim($msg->MsgId),
		);
		
		$res = $this->_db->getMWxMessages()->insert($msg_data);
		
		if ($res) {
			$content = "您发送的消息类型为：" . $msg_data['msg_type'] . "，内容为：" . $msg_data['content']['title'];

			echo sprintf($this->_msg_tpl['text'], $msg_data['from'], $msg_data['to'], $msg_data['create_ts'], $content);
		} else {
			throw new JuneException('消息保存失败！');
		}
	}
	
	/**
	 * （被动）发送事件响应消息
	 * 
	 * @param object $event
	 * 
	 * @author 安佰胜
	 */
	public function responseEvent($event) {
		$event_type = trim($event->Event);
		$create_ts  = trim($event->CreateTime);
		$to   = trim($event->ToUserName);
		$from = trim($event->FromUserName);
		
		// 发消息时，需要调转发送方和接收方！！！
		switch ($event_type) {
			// 订阅事件（包括未关注公众号扫描二维码时的事件）
			case 'subscribe':
				if (!empty($event->EventKey)) {
					// 此处场景参数带前缀"qrscene_"
					$content = "场景参数：" .trim($event->EventKey) . "，感谢您的关注！更多精彩内容请点击下方的菜单！";
				} else {
					$content = "感谢您的关注！更多精彩内容请点击下方的菜单！";
				}

				echo sprintf($this->_msg_tpl['text'], $from, $to, $create_ts, $content);
				
				break;
			// 已关注公众号扫描二维码时的事件
			case 'SCAN':
				// 此处场景参数无前缀
				$scene_id = trim($event->EventKey);
				$content = "您扫描的二维码场景参数为：$scene_id";
				
				echo sprintf($this->_msg_tpl['text'], $from, $to, $create_ts, $content);
				
				break;
			// 上报地理位置时的事件
			case 'LOCATION':
				$lat = trim($event->Latitude);
				$lon = trim($event->Longitude);
				$content = "您的位置为：纬度（".$lat."）、经度（".$lon."）";
				
				echo sprintf($this->_msg_tpl['text'], $from, $to, $create_ts, $content);
				
				break;
		}
	}
	
	/**
	 * 向用户发送模板消息
	 * 
	 * @param string $to
	 * @param string $tpl_id
	 * @param string $url
	 * @param array $data
	 * 
	 * @author 安佰胜
	 */
	public function sendTplMessage($to, $tpl_id, $url, $data) {
		if (empty($to) || empty($tpl_id) || empty($url) || empty($data)) {
			throw new JuneException('参数丢失！');
		}
		
		$msg = array('touser' => $to,
					 'template_id' => $tpl_id,
					 'url' => $url,
					 'data' => $data
		);
		
		// 获取access_token，有效期7200秒
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
		
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => json_encode($msg), 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 发送客服消息
	 * 
	 * 
	 * @author 安佰胜
	 */
	public function sendCustomMessage() {
		
	}
}
?>