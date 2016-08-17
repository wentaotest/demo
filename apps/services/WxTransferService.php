<?php namespace June\apps\services;

use June\Core\JuneException;

/**
 * 微信转账服务类
 * 
 * @author Vincent An
 */

class WxTransferService {
	static private $_inst;
	
	private $_db;
	
	private $_tokens = array(
			'mch_appid' => null, // 微信公众号appid
			'mchid'     => null, // 微信支付分配的商户号
			'key'       => null,
	);
	
	private $_certs  = array(
			'api_cert' => null,
			'api_key'  => null,
			'rootca'   => null,
	);
	
	private $_req_params = array();
	
	private $_api_url = array(
			'transfers'         => 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers',
			'get_transfer_info' => 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo'
	);
	
	private function __construct($config) {
		
		foreach ($this->_tokens as $k => $v) {
			if (!empty($config[$k])) {
				$this->_tokens[$k] = $config[$k];
			} else {
				$msg = '微信转账服务类构造函数参数错误！参数名称：' . $k;
				throw new JuneException($msg, JuneException::REQ_ERR_PARAMS_MISSING);
			}
		}
		
		foreach ($this->_certs as $k => $v) {
			if (is_file($config[$k])) {
				$this->_certs[$k] = $config[$k];
			} else {
				$msg = '微信转账服务类证书文件错误！证书名称：' . $k;
				throw new JuneException($msg, JuneException::REQ_ERR_PARAMS_MISSING);
			}
		}
		
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance($config) {
		if (empty(self::$_inst)) {
			self::$_inst = new WxTransferService($config);
		}

		return self::$_inst;
	}
	
	/**
	 * 企业向个人转账
	 *
	 * 接口调用规则：
	 *
	 * 		1、给同一个实名用户付款，单笔单日限额2W/2W
	 * 		2、给同一个非实名用户付款，单笔单日限额2000/2000
	 * 		3、一个商户同一日付款总额限额100W
	 * 		4、单笔最小金额默认为1元
	 * 		5、每个用户每天最多可付款10次，可以在商户平台--API安全进行设置
	 * 		6、给同一个用户付款时间间隔不得低于15秒
	 *
	 * @param string $open_id      用户open_id
	 * @param integer $amount      转账金额，单位：分
	 * @param string $trade_no     转账单号，企业自己生产，需保持唯一性
	 * @param string $desc         付款描述信息
	 * @param string $chk_name     是否校验收款人姓名NO_CHECK：不校验真实姓名；FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账）；OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
	 * @param string $re_user_nm   收款人姓名
	 *
	 * @author Vincent An
	 */
	public function transfer($open_id, $amount, $trade_no, $desc, $chk_name = 'NO_CHECK', $re_user_nm = null) {
		if (empty($open_id) || empty($amount) || empty($trade_no) || empty($desc)) {
			throw new JuneException('微信转账参数缺失！', JuneException::REQ_ERR_PARAMS_MISSING);
		}
		
		if ($chk_name !== 'NO_CHECK' && empty($re_user_nm)) {
			throw new JuneException('微信转账实名认证错误，收款人姓名不能为空！', JuneException::REQ_ERR_PARAMS_MISSING);
		}
		
		$this->setReqParam('openid', $open_id);
		$this->setReqParam('amount', $amount);
		$this->setReqParam('partner_trade_no', $trade_no);
		$this->setReqParam('desc', $desc);
		$this->setReqParam('check_name', $chk_name);
		
		
		if ($chk_name !== 'NO_CHECK') {
			$this->setReqParam('re_user_name', $re_user_nm);
		}
		
		$req_xml = $this->_createReqXml();
		
		$res = curl_ssl($this->_api_url['transfers'], $req_xml, $this->_certs, 'xml');
		
		$ret_val = null;
		switch ($res['return_code']) {
			case 'SUCCESS':
				if ($res['result_code'] == 'SUCCESS') {
					$ret_val = $res;
				} else {
					$err_code = !empty($res['err_code']) ? $res['err_code'] : 'UNKNOWN';
					$err_msg = !empty($res['err_code_des']) ? $res['err_code_des'] : '微信转账请求接口错误！';
					
					throw new JuneException($err_msg . "微信支付错误码：" . $err_code);
				}
				break;
			case 'FAIL':
			default:
				$err_code = !empty($res['err_code']) ? $res['err_code'] : 'UNKNOWN';
				$err_msg = !empty($res['err_code_des']) ? $res['err_code_des'] : '微信转账请求接口错误！';
					
				throw new JuneException($err_msg . "微信支付错误码：" . $err_code);
				break;
		}
		
		return $ret_val;
		
	}
	
	public function getTransferInfo() {
	
	}
	
	/**
	 * 设置微信转账接口请求参数
	 * 
	 * @param string $name
	 * @param mixed $value
	 * 
	 * @author Vincent An
	 */
	public function setReqParam($name, $value) {
		$this->_req_params[$name] = $value;
	}
	
	/**
	 * 获取微信转账接口请求参数
	 * 
	 * @param string $name
	 * @throws JuneException
	 * @return mixed
	 * 
	 * @author Vincent An
	 */
	public function getReqParam($name) {
		if (isset($this->_req_params[$name])) {
			return $this->_req_params[$name];
		} else {
			throw new JuneException('微信转账服务类成员属性值不存在！属性名称：' . $name, JuneException::MEMBER_PROPERTY_NOT_EXISTS);
		}
	}
	
	/**
	 * 获取商户编号
	 * 
	 * @return string 
	 * 
	 * @author Vincent An
	 */
	public function getMchId() {
		return $this->_tokens['mchid'];
	}
	
	/**
	 * 获取商户微信公众号编号
	 * 
	 * @return string 
	 *
	 * @author Vincent An
	 */
	public function getMchAppId() {
		return $this->_tokens['mch_appid'];
	}
	
	/**
	 * 获取商户接口密钥
	 * 
	 * @return string
	 * 
	 * @author Vincent An
	 */
	public function getKey() {
		return $this->_tokens['key'];
	}
	
	/**
	 * 生成转账所需的商家交易流水号
	 * 
	 * @param string $app_id
	 * @return string 转账交易流水号
	 * 
	 * @author Vincent An
	 */
	public function genTradeNo($app_id) {
		$this->_last_trade_no = $this->getMchId() . $app_id . date("YmdHis", time()) . rand(100, 999);
		
		return $this->_last_trade_no;
	}
	
	/**
	 * 获取刚刚生成的转账交易流水号
	 * 
	 * @return string 转账交易流水号
	 * 
	 * @author Vincent An
	 */
	public function getLastTradeNo() {
		return $this->_last_trade_no;
	}
	
	/**
	 * 生成微信转账服务接口所需的xml格式参数
	 * 
	 * @throws JuneException
	 * 
	 * @author Vincent An
	 */
	private function _createReqXml() {
		$this->setReqParam('mch_appid', $this->getMchAppId());
		$this->setReqParam('mchid', $this->getMchId());
		$this->setReqParam('nonce_str', getCode(32, 2));
		$this->setReqParam('spbill_create_ip', get_server_ip());
		
		// 生成签名
		$sign = self::makeSignature($this->_req_params, $this->getKey());
		
		$this->setReqParam('sign', $sign);
		
		// 参数数组转化为xml
		$xml = array_to_xml($this->_req_params);
		
		if (!$xml) {
			throw new JuneException('微信转账时发生错误，原因：生成XML时参数格式非法！', JuneException::REQ_ERR_PARAMS_INVALID);
		}
		
		return $xml;
	}
	
	/**
	 * 生成签名
	 * 
	 * @param array $req_params
	 * @param string $key
	 * @return string $sign
	 * 
	 * @author Vincent An
	 */
	static public function makeSignature($req_params, $key) {
		// 签名步骤一：按字典序排序参数，生成query_string，拼接KEY值
		ksort($req_params);
		$q_str = array_to_query_string($req_params) . "&key=" . $key;
		
		// 签名步骤二：MD5加密，转成大写
		$sign = strtoupper(md5($q_str));
		
		return $sign;
	}
	
}