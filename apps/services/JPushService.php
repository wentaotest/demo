<?php namespace June\apps\services;

require_once './libs/Plugins/JPush/Client.php';
require_once './libs/Plugins/JPush/Config.php';
require_once './libs/Plugins/JPush/PushPayload.php';
require_once './libs/Plugins/JPush/Http.php';

use June\Core\JuneException;

/**
 * 极光推送类
 * 注意: 使用 JPush类时应写为 \JPush
 *
 * @author 李优
 */
class JPushService {
    static private $_inst;
    private $_db;
    private $_jpush;
    
    // 构造函数
    private function __construct($app_key,$master_secret) {
    	$this->_db  = june_get_apps_db_connection();
    	
        // 实例化
        $this->_jpush = new \JPush\Client($app_key, $master_secret);
    }

    // 单例实例化
    static public function getInstance($app_key,$master_secret) {
        if (empty(self::$_inst)) {
            self::$_inst = new JPushService($app_key,$master_secret);
        }
        
        return self::$_inst;
    }

    /**
     * 简单推送示例
     * @author 
     */
    public function simple() {
        // 简单推送示例
		$result = $this->_jpush->push()
		    ->setPlatform('all')
		    ->addAllAudience()
		    ->setNotificationAlert('Hi, JPush') // 此部分内容不会显示
		    ->addAndroidNotification('内容', '标题', 1, array("key1"=>"hello", "key2"=>"你好"))
		    ->send();
		    
		return json_encode($result);
	}
	
	/**
	 * 完整的推送示例,包含指定Platform,指定Alias,Tag,指定iOS,Android notification,指定Message等
	 * @author 
	 */
	public function whole() {
		$result = $this->_jpush->push()
    			->setPlatform(array('ios', 'android'))
    			->addAlias('15116918840')
    			//->addTag(array('tag1', 'tag2'))
			    ->setNotificationAlert('Hi, JPush')
			    ->addAndroidNotification('Hi, android notification', 'notification title', 1, array("key1"=>"value1", "key2"=>"value2"))
			    ->addIosNotification("Hi, iOS notification", 'iOS sound', 0x10000, true, 'iOS category', array("key1"=>"value1", "key2"=>"value2"))
			    ->setMessage("msg content", 'msg title', 'type', array("key1"=>"value1", "key2"=>"value2"))
			    ->setOptions(100000, 3600, null, false)
			    ->send();

		return json_encode($result);
	}
	
	/**
	 * 也可以提前准备好所有的参数，然后链式调用，这样代码可读性更好一点
	 * @author 
	 */
	public function last() {
		$platform = array('ios', 'android');
		$alert = 'Hello JPush';
		$tag = array('tag1', 'tag2');
		$regId = array('rid1', 'rid2');
		$ios_notification = array(
		    'sound' => 'hello jpush',
		    'badge' => 2,
		    'content-available' => true,
		    'category' => 'jiguang',
		    'extras' => array(
		        'key' => 'value',
		        'jiguang'
		    ),
		);
		$android_notification = array(
		    'title' => 'hello jpush',
		    'build_id' => 2,
		    'extras' => array(
		        'key' => 'value',
		        'jiguang'
		    ),
		);
		$content = 'Hello World';
		$message = array(
		    'title' => 'hello jpush',
		    'content_type' => 'text',
		    'extras' => array(
		        'key' => 'value',
		        'jiguang'
		    ),
		);
		$options = array(
		    'sendno' => 100,
		    'time_to_live' => 100,
		    'override_msg_id' => 100,
		    'big_push_duration' => 100
		);
		$response = $this->_jpush->push()->setPlatform($platform)
		    ->addTag($tag)
		    ->addRegistrationId($regId)
		    ->iosNotification($alert, $ios_notification)
		    ->androidNotification($alert, $android_notification)
		    ->message($content, $message)
		    ->options($options)
		    ->send();
	
		return json_encode($response);
	}
	
	/**
	 * 根据条件推送消息
	 * 
	 * @param $title
	 * @param $content
	 * @param $platform 推送平台设置,all-所有平台；android-安卓平台；ios-苹果平台；winphone平台暂不考虑
	 * @param $audience 目标用户
	 * @param $extras 附加字段
	 * @param $live_time 保存离线时间的秒数默认为一天(可不传)单位为秒
	 * @param $msg_type 消息类型:1-通知;2-消息
	 * 
	 */
	public function pushMessage($title='测试',$content='测试',$platform='all',$audience='all',$msg_type=1,$extras=array(),$live_time='86400'){
		$data = array();
	
		$data['platform'] = $platform; // 目标用户终端手机的平台类型android,ios,winphone
		$data['audience'] = $audience; // 目标用户
	
		$notification = array(
				// 统一的模式--标准模式
				//"alert" => $content,
				// 安卓自定义
				"android" => array(
						"alert" => $content,
						"title" => $title,
						"builder_id" => 1,
						"extras" => array('msg_type' => $msg_type)
				),
				// ios的自定义
				"ios" => array(
						"alert" => $content,
						"badge" => "+1",
						"sound" => "default",
						"extras" => array('msg_type' => $msg_type)
				),
		);
		
		$data['notification'] = $notification;
		
		//附加选项
		$data['options'] = array(
				"sendno" => time(),
				"time_to_live" => $live_time, // 保存离线时间的秒数默认为一天
				"apns_production" => false, // 指定 APNS通知发送环境：false-开发环境；true-生产环境
		);
		
		// 简单推送
		//$res = $this->simpleSend($title,$content,'ios',2);
		
		// 个人推送
		$res = $this->sendToOne($title,$content,'15116918840',1);
		
		//$res = $this->_db->getMPushMsgs()->pushWinnerMsg('107040004','15116918840','412376');
		
		return $res;
	}
	
	/**
	 * 消息类型处理
	 * 
	 * @param string $title 推送标题
	 * @param string $content 推送内容
	 * @param string $platform 推送的平台
	 * @param array $extras 额外参数
	 * @return object 
	 *
	 * @author 李优
	 */
	public function getPush($title,$content,$platform = 'all',$extras = array()){
		if (empty($content)) {
			throw new JuneException('推送内容为空！', JuneException::REQ_ERR_PARAMS_MISSING);
		}
		
		// Android通知数据
		$android_notification = array(
				'title' => $title,
				'build_id' => 2,
				'extras' => $extras
		);
		
		// iOS通知数据
		$ios_notification = array(
				'sound' => 'default',
				'badge' => '+1', // ios中app图标右上角显示的数字，即是第几条消息，+1是累加；0是清除不显示；固定数字则显示固定值
				'extras' => $extras
		);
		
		// 自定义消息内容
		$message = array(
				'title' => $title,
				'content_type' => 'text',
				'extras' => $extras
		);
		
		// 可选项
		$options = array(
				"sendno" => time(),
				"time_to_live" => C('jpush_conf', 'jpush_user.time_to_live'), // 保存离线时间的秒数默认为一天
				//"apns_production" => C('web_conf','debug') == 'development' ? false : true // 指定 APNS通知发送环境：false-开发环境；true-生产环境
				"apns_production" => true 
		);
		
		// 通知和消息处理
		$obj = $this->_jpush->push()
				->setPlatform($platform)
				->androidNotification($content, $android_notification)
				->iosNotification($content, $ios_notification)
				->message($content, $message)
				->options($options);
		
		return $obj;
	}
	
	/**
	 * 简单推送     推送所有用户、Android用户和iOS用户
	 * 
	 * @param string $title 推送标题
	 * @param string $content 推送内容
	 * @param string $platform 推送的平台
	 * @param array $extras 额外参数
	 * @return json
	 *
	 * @author 李优
	 */
	public function simpleSend($title,$content,$platform = 'all',$extras = array()){
		$response = $this->getPush($title,$content,$platform,$extras)
				->addAllAudience()->send();
	
		return json_encode($response);
	}
	
	/**
	 * 给单个用户推送
	 * 
	 * @param string $title 推送标题
	 * @param string $content 推送内容
	 * @param string $mob_num 用户手机号
	 * @param array $extras 额外参数
	 * @return json
	 * 
	 * @author 李优
	 */
	public function sendToOne($title,$content,$mob_num,$extras = array()){
		if (empty($mob_num)) {
			throw new JuneException('推送单个用户时参数错误！', JuneException::REQ_ERR_PARAMS_MISSING);
		}
		
		$response = $this->getPush($title,$content,'all',$extras)->addAlias($mob_num)->send();
		
		return json_encode($response);
	}
	
}