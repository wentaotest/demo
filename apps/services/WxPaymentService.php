<?php namespace June\apps\services;

require_once './libs/Plugins/WxPay/WxPay.Api.php';
require_once './libs/Plugins/WxPay/WxPay.Config.php';
require_once './libs/Plugins/WxPay/unit/WxPay.JsApiPay.php';
require_once './libs/Plugins/WxPay/unit/log.php';

/**
 * 微信APP支付服务类
 *
 * @author 安佰胜
 */

class WxPaymentService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxPaymentService();
		}

		return self::$_inst;
	}
	
	/**
	 * 微信支付统一下单
	 *
	 * @param array $data 待签名部分参数
	 * @param string $type 下单类型
	 * @author 李优 & Vincent An
	 */
	public function unifiedOrder(array $data, $type = null) {
		$wx_pay_api           = new \WxPayApi();
		$m_pay_logs           = $this->_db->getMPaymentLogs();
		$wx_pay_unified_order = new \WxPayUnifiedOrder();
		
		// 整理参数
		$order_sn  = $data['out_trade_no'];
		$total_fee = $data['total_fee'];
		
		// 属性赋值
		foreach ($data as $k => $v) {
			$func = 'Set' . ucfirst($k);
			if (method_exists($wx_pay_unified_order, $func)) {
				$wx_pay_unified_order->$func($v);
			}
		}
		
		// 调用微信下单接口
		$order_res = $wx_pay_api->unifiedOrder($wx_pay_unified_order, $type);
		$order_res['noncestr'] = $wx_pay_unified_order->GetNonce_str();
		
		// 是否下单成功
		$is_succeeded = false;
		
		// 下单成功后，为客户端调用支付接口生成签名
		if ($order_res['return_code'] == 'SUCCESS' && $order_res['result_code'] == 'SUCCESS') {
			$is_succeeded = true;
			
			// 生成支付签名所需的参数
			$sign_params = array(
					'prepayid'  => $order_res['prepay_id'],
					'package'   => 'Sign=WXPay',
					'noncestr'  => $order_res['noncestr'],
					'timestamp' => time()
			);
			
			// 生成支付签名
			$sign_data = $this->makeSignature($sign_params);
		}
		
		// 记录微信下单日志
		$reasons = array('code' => $order_res['return_code'], 'msg' => $order_res['return_msg']);
		$content = json_encode($order_res);
		
		$m_pay_logs->createWxPayUnifiedOrderLog($is_succeeded, $type, $order_sn, $total_fee, $reasons, $content);
		
		// 返回结果
		return $is_succeeded ? $sign_data : false;
	}
	
	/**
	 * 生成微信支付签名
	 * 
	 * @param array $data 待签名部分参数
	 * @author 李优
	 */
	public function makeSignature(array $data) {
		$WxPayUnifiedOrder = new \WxPayUnifiedOrder();
		
		$data['partnerid'] = \WxPayConfig::MCHID;
		$data['appid'] = \WxPayConfig::APPID;
		
		//签名步骤一：按字典序排序参数
    	ksort($data);
    	$string = $this->toUrlParams($data);
    	//签名步骤二：在string后加入KEY
    	$string = $string . "&key=".\WxPayConfig::KEY;
    	//签名步骤三：MD5加密
    	$string = md5($string);
    	//签名步骤四：所有字符转为大写
    	$sign = strtoupper($string);
    	
    	$data['sign'] = $sign;
    	
    	return $data;
	}
	
	/**
	 * 格式化参数格式化成url参数
	 * 
	 * @param array $data 待签名部分参数
	 * @author 李优
	 */
	public function toUrlParams(array $data){
		$buff = "";
		foreach ($data as $k => $v){
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
	
		$buff = trim($buff, "&");
		
		return $buff;
	}
	
	/**
	 * 微信支付查询
	 *
	 * @param string $transaction_id 微信订单号
	 * @param string $out_trade_no 商户订单号
	 * @author 李优
	 */
	public function wxOrderQuery($transaction_id,$out_trade_no){
		$WxPayOrderQuery = new \WxPayOrderQuery();
		
		$val = !empty($transaction_id) ? $transaction_id : $out_trade_no;
		$func = 'Set'.ucfirst($val);
		
		if (method_exists($WxPayOrderQuery, $func)) {
			$WxPayOrderQuery->$func($val);
		}
		
		$wxpay = new \WxPayApi();
		$result = $wxpay->orderQuery($WxPayOrderQuery);
		
		$return = false;
		if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
			//判断交易状态
			$return = $result['trade_state'] == 'SUCCESS' ? true : false;
			
			$this->_db->getMTestLogs()->writeLog('微信查询交易状态trade_state：'.$result['trade_state'].';订单号：'.$val,'wxquery');
			pay_log('微信查询交易状态trade_state：'.$result['trade_state'].';订单号：'.$val,'wx_query.log');
		}else{
			$this->_db->getMTestLogs()->writeLog('微信查询err_code_des：'.$result['err_code_des'].';err_code:'.$result['err_code'],'wxquery');
			pay_log('微信查询err_code_des：'.$result['err_code_des'].';err_code:'.$result['err_code'],'wx_query.log');
		}
		
		return $return;
	}
	
	/**
	 * 微信支付退款
	 *
	 * @param array $data 待签名部分参数
	 * @author 李优
	 */
	public function wxRefund(array $data){
		$WxPayRefund = new \WxPayRefund();
		
		$data['op_user_id'] = \WxPayConfig::MCHID;
		
		foreach ($data as $k => $v) {
			$func = 'Set'.ucfirst($k);
			if (method_exists($WxPayRefund, $func)) {
				$WxPayRefund->$func($v);
			}
		}
			
		$wxpay = new \WxPayApi();
		$result = $wxpay->refund($WxPayRefund);
		
		$param = array();
		if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
			//退款成功后返回数据
			$param = array(
					'refund_fee'  => $result['refund_fee'], // 退款金额
					'refund_id'   => $result['refund_id'], // 微信退款单号
					'out_refund_no' => $result['out_refund_no'] // 商户退款单号
			);
			
			$this->_db->getMTestLogs()->writeLog('微信退款成功，返回数据：'.json_encode($param),'wxrefund');
			pay_log('微信退款成功，返回数据：'.json_encode($param),'wx_refund.log');
		}else{
			$this->_db->getMTestLogs()->writeLog('微信退款失败err_code_des：'.$result['err_code_des'].';err_code:'.$result['err_code'],'wxrefund');
			pay_log('微信退款失败err_code_des：'.$result['err_code_des'].';err_code:'.$result['err_code'],'wx_refund.log');
		}
		
		return $param;
	}
	
	/**
	 * 生成充值订单流水号（此时跟商品无关，与商品订单不同）
	 * 
	 * @author Vincent An
	 */
	public function genRechargeOrderSn() {
		$ts = time();
		
		$key_name = 'recharge_order_sn_' . date('Ymd', $ts);
		$idx = june_get_apps_db_conn_pool()->getMAutoIncIds()->getIncId($key_name);
		
		// 生成订单流水号
		$sn = "R-" . date('Ymd', $ts) . str_pad($idx, 10, '0', STR_PAD_LEFT);
		
		return $sn;
	}
	
	/**
	 * 响应微信支付回调
	 * 
	 * @param string $code
	 * @param string $msg
	 * 
	 * @author Vincent An
	 */
	public function sendWxPayNotifyReply($code, $msg) {
		$wx_pay_notify_reply = new \WxPayNotifyReply();
		
		$wx_pay_notify_reply->SetData('return_code', $code);
		$wx_pay_notify_reply->SetData('return_msg', $msg);
		
		echo $wx_pay_notify_reply->ToXml();exit;
	}
	
}