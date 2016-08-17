<?php namespace June\apps\services;

class SmsService {
    static private $_inst;
	
    /**
     * 手机短信服务类构造函数
     * 
     * @author 安佰胜
     */
    private function __construct() {
    }
	
    /**
     * 获取服务类的实例
     * 
     * @return June\apps\services\SmsService
     * 
     * @author 安佰胜
     */
    static public function getInstance() {
        if (empty(self::$_inst)) {
            self::$_inst = new SmsService();
        }

        return self::$_inst;
    }
	
    /**
     * 获取用户手机号码
     * 
     * @param MongoId $uid 用户编号
     * @param string $tc_name 集合（表）名称
     * 
     * @author 安佰胜
     */
    public function getUserPhoneNum($uid, $tc_name) {

    }
	
    /**
     * 创建短信内容
     * 
     * @param string $phone_num
     * @param string $content
     * @param string $type
     * @return string $message
     * 
     * @author 安佰胜 & 李优
     */
    public function createMessage($phone_num, $content, $type = null) {
    	if ($type == 'refund') {
    		$content = explode(':', $content);
    		
    		$tpl_id = C('sms_conf', 'juhe.refund_tpl.tpl_id');
    		$tpl_order = C('sms_conf', 'juhe.refund_tpl.tpl_order') . $content[0] . '&';
    		$tpl_money = C('sms_conf', 'juhe.refund_tpl.tpl_money') . $content[1];
    		$tpl_value = $tpl_order . $tpl_money;
    	} else {
    		$tpl_id = C('sms_conf', 'juhe.auth_tpl.tpl_id');
    		$tpl_value = C('sms_conf', 'juhe.auth_tpl.tpl_value');
    	}
    	
    	$message = array(
    		'key'       => C('sms_conf', 'juhe.api_key'), //申请的接口APPKEY
			'mobile'    => $phone_num, // 收信人手机号码
			'tpl_id'    => $tpl_id, // 短信模板ID，根据实际情况修改
			'tpl_value' => $type == 'refund' ? $tpl_value : $tpl_value . $content, // 设置的模板变量，根据实际情况修改
			);
		
    	return $message;
    }
    
    /**
     * 创建通知幸运用户短信内容
     *
     * @param string $phone_num
     * @param string $content
     * @return string $message
     *
     * @author 李优
     */
    public function createLuckyMessage($phone_num, $content) {
    	$tpl_lotto_sn = C('sms_conf', 'juhe.winner_tpl.tpl_ltsn') . $content['lotto_sn'] . '&';
    	$tpl_lucky_code = C('sms_conf', 'juhe.winner_tpl.tpl_lcode') . $content['lucky_num'] . '&';
    	$tpl_name = C('sms_conf', 'juhe.winner_tpl.tpl_name') . $content['p_name'] . '&';
    	$tpl_exchange_code = C('sms_conf', 'juhe.winner_tpl.tpl_ecode') . $content['code'];
    	
    	$tpl_value = $tpl_lotto_sn . $tpl_lucky_code . $tpl_name . $tpl_exchange_code;
    	
    	$message = array(
    			'key'       => C('sms_conf', 'juhe.api_key'), //申请的接口APPKEY
    			'mobile'    => $phone_num, // 收信人手机号码
    			'tpl_id'    => C('sms_conf', 'juhe.winner_tpl.tpl_id'), // 短信模板ID，根据实际情况修改
    			'tpl_value' => $tpl_value, // 设置的模板变量，根据实际情况修改
    	);
    
    	return $message;
    }
	
    /**
     * 发送短信
     * 
     * @param string $message
     * @return array $ret
     * 
     * @author 安佰胜
     */
    public function send($message) {
    	$api_url = C('sms_conf', 'juhe.api_url'); //短信接口的URL
	    
	    $response = $this->_juheCurl($api_url, $message, 1); //请求发送短信

	    $res = json_decode($response, true);

	    $ret = array();
	    if ($res['error_code'] === 0) {
	    	// 短信发送成功
	    	$ret['error'] = $res['error_code'];
	    	$ret['sid'] = $res['result']['sid'];
	    } else {
	    	// 短信发送失败
	    	$ret['error'] = $res['error_code'];
	    	$ret['msg'] = $res['reason'];
	    }

	    return $ret;
    }

    /**
	 * 请求接口返回内容
	 *
	 * @param  String $url 请求的URL地址
	 * @param  Mixed $params 请求的参数，类型取决于$is_post
	 * @param  Integer $is_post 是否采用POST形式 1-post 0-get
	 * @return  String
	 * 
	 * @author 安佰胜
	 */
    private function _juheCurl($url, $params=false, $is_post=0) {
    	$httpInfo = array();
	    $ch = \curl_init();
	 
	    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    
	    $params_str = "";
	    if (is_array($params)) {
	    	foreach ($params as $k => $v) {
	    		$params_str .= "&$k=$v";
	    	}
	    	$params_str = trim($params_str, '&');
	    }

	    if ($is_post) {
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	        curl_setopt($ch, CURLOPT_URL, $url);
	    } else {
	        if ($params_str) {
	            curl_setopt($ch, CURLOPT_URL, $url.'?'.$params_str);
	        } else {
	            curl_setopt($ch, CURLOPT_URL, $url);
	        }
	    }

	    $response = curl_exec($ch);
	    if ($response === FALSE) {
	        return false;
	    }

	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
	    curl_close($ch);

	    return $response;
    }
    
    
}