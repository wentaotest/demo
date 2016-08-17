<?php namespace June\apps\services;

use June\Core\JuneException;
/**
 * 内部使用的盐，在生成系统中绝不要修改该变量，除非有特别重要的需求！！！
 */
define("XPATH_CRYPTO_SALT", " - Xpath's users should be always love the salt we give them");
/**
 * 内部用来异或一个整型数的值，避免将明文码值显示给用户
 */
define("XPATH_CRYPTO_XOR_SALT", 0x730F729E);

class TripleDesService {
	private $_secret;
		
	public function __construct($key = "", $add_salt = true) {
		$this->setKey($key, $add_salt);
	}
	

	public function setKey($key, $add_salt = true) {
		if($add_salt) {
			$key = $key . XPATH_CRYPTO_SALT;
		}
		$this->_secret = md5($key);
	}

	private function getKey(){
		return $this->_secret;
	}
	
	/**
	 * 加密函数
	 * 
	 * @param string $value
	 * @throws JuneException
	 * @return string
	 * 
	 * @author 安佰胜
	 */
	public function encrypt($value){
		if(empty($value)) {
			throw new JuneException("被加密的字符串不能为空值！");
		}
		
		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
		$key = substr($this->getKey(), 0, mcrypt_enc_get_key_size($td));
		mcrypt_generic_init($td, $key, $iv);
		$ret = urlencode(base64_encode(mcrypt_generic($td, $value)));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		return $ret;
	}

	/**
	 * 解密函数
	 * 
	 * @param string $value
	 * @throws JuneException
	 * @return string
	 * 
	 * @author 安佰胜
	 */
	public function decrypt($value){
		if(empty($value)) {
			throw new JuneException("被解密的字符串不能为空值！");
		}
		
		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
		$key = substr($this->getKey(), 0, mcrypt_enc_get_key_size($td));
		mcrypt_generic_init($td, $key, $iv);
		$ret = trim(mdecrypt_generic($td, base64_decode(urldecode($value))));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		return $ret;
	}
}
?>