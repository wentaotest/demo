<?php namespace June\apps\services;

require_once './libs/Plugins/AliPay/alipay.config.php';
require_once './libs/Plugins/AliPay/lib/alipay_core.function.php';
require_once './libs/Plugins/AliPay/lib/alipay_rsa.function.php';
require_once './libs/Plugins/AliPay/lib/alipay_notify.class.php';

/**
 * 支付宝支付服务类
 *
 * @author 李优
 */

class AliPaymentService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new AliPaymentService();
		}

		return self::$_inst;
	}
	
	/**
	 * 生成签名
	 * 
	 * @param array $data
	 * @param string $type
	 * @return string
	 * @author 李优
	 */
	public function makeSignature($data,$type = null){
		if($type == 'recharge'){
			$notify_url = '"'.\AliPayConfig::NOTIFY_URL_RECHARGE.'"';
		}else{
			$notify_url = '"'.\AliPayConfig::NOTIFY_URL.'"';
		}
		
		$sign_data = array(
				'_input_charset' => '"'.\AliPayConfig::INPUT_CHARSET.'"',
				'body' => '"'.$data['p_desc'].'"',
				'notify_url' => $notify_url,
				'out_trade_no' => '"'.$data['order_sn'].'"',
				'partner' => '"'.\AliPayConfig::PARTNER.'"',
				'payment_type' => '"'.\AliPayConfig::PAYMENT_TYPE.'"',
				'seller_id' => '"'.\AliPayConfig::SELLERID.'"',
				'service' => '"'.\AliPayConfig::SERVICE.'"',
				'subject' => '"'.$data['p_name'].'"',
				'total_fee' => '"'.$data['money'].'"'
		);
		
		//对待签名参数数组排序，可以省略
		$para_sort = argSort($sign_data);
		
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$signstr = createLinkstring($para_sort);
		
		//生成签名
		$sign = rsaSign($signstr, \AliPayConfig::PRIVATE_KEY_PATH);
		
		//注意：生成签名后必须进行urlencode()编码
		$sign = urlencode($sign);
		
		//拼接sign、sign_type
		$sign_data['sign'] = '"'.$sign.'"';
		$sign_data['sign_type'] = '"'.\AliPayConfig::SIGN_TYPE.'"';
		
		//最终请求参数
		$final_str = createLinkstring($sign_data);
		
		return $final_str;
	}
	
	/**
	 * 支付宝通知验证，包括签名验证和是否是支付宝发来的通知验证
	 *
	 * @return boolean  验证结果
	 * @author 李优
	 */
	public function verifyNotify($data) {
		$alipay_config = array(
				'sign_type' => \AliPayConfig::SIGN_TYPE,
				'ali_public_key_path' => \AliPayConfig::ALI_PUBLIC_KEY_PATH,
				'partner' => \AliPayConfig::PARTNER,
				'transport' => \AliPayConfig::TRANSPORT,
				'cacert' => \AliPayConfig::CACERT 
		);
		
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify($data);
		
		return $verify_result ? true : false;
	}
	
	/**
	 * 验证返回的out_trade_no、total_fee、seller_id是否和本地一致，且订单是否处理过
	 *
	 * @param string $out_trade_no
	 * @param integer $total_fee
	 * @param string $seller_id
	 * @return boolean  验证结果
	 * @author 李优
	 */
	public function verifyParam($out_trade_no,$total_fee,$seller_id) {
		// 查询订单信息
		$criteria = array('sn' => $out_trade_no);
		$fields = array('total_bets' => true,'status' => true);
		
    	$order_info = $this->_db->getMOrders()->findOne($criteria,$fields);
	
    	if(empty($order_info) || $order_info['status'] == 1 || $order_info['total_bets'] != $total_fee || \AliPayConfig::SELLERID != $seller_id){
    		return false;
    	}
    	
//     	//先不验证金额数
//     	if(empty($order_info) || $order_info['status'] == 1 || \AliPayConfig::SELLERID != $seller_id){
//     		return false;
//     	}
    	
		return true;
	}
	
	/**
	 * 验证返回的out_trade_no、seller_id是否和本地一致，且订单是否处理过
	 *
	 * @param string $out_trade_no
	 * @param string $seller_id
	 * @return boolean  验证结果
	 * @author 李优
	 */
	public function verifyInfo($out_trade_no,$seller_id) {
		// 查询订单是否存在
		$criteria = array('order_sn' => $out_trade_no);
		$fields = array('_id' => true);
	
		$exp_logs = $this->_db->getMExpLogs()->findOne($criteria,$fields);
	
		if(!empty($exp_logs) || \AliPayConfig::SELLERID != $seller_id){
			return false;
		}
		 
		return true;
	}
	
	/**
	 * 验证退款时是否已经做过了这次通知返回的处理
	 *
	 * @param string $batch_no 退款批次号
	 * @return boolean  验证结果
	 * @author 李优
	 */
	public function verifyRefund($batch_no) {
		// 查询该批次的退款记录
    	$refund_logs = $this->_db->getMRefundLogs()->find(array('refund_sn' => $batch_no),array('status' => true)); 
	
    	$res = true;
		if(!empty($refund_logs)){
			foreach($refund_logs as $log){
				if($log['status'] == 1){
					$res = false;
					break;
				}
			}
		}else{
			$res = false;
		}
			
		return $res;
	}
	
}