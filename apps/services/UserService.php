<?php namespace June\apps\services;

use June\Core\JuneException;
use June\apps\services\TripleDesService;
use June\apps\services\UploadService;

class UserService {
	static private $_inst;
	private $_db;
	private $_tc_name;

	private function __construct($db, $tc_name = null) {
		$this->_db = $db;
		$this->_tc_name = $tc_name;
	}

	static public function getInstance($db, $tc_name=null) {
		if (empty(self::$_inst)) {
			self::$_inst = new UserService($db, $tc_name);
		}
		return self::$_inst;
	}
	
	/**
     * 用户登录
     * @param String $mob_num 用户手机号或用户名
     * @param String $pwd 密码
     * @return Array $info 
     * @author 孟振国&刘富
     */
	public function login($mob_num, $pwd) {
		if (empty($mob_num) || empty($pwd)) {
	    	throw new JuneException('手机号或密码不能为空！', JuneException::REQ_ERR_PARAMS_MISSING);
	    }
	    if(!$this->checkPhone($mob_num)) {
	    	throw new JuneException('手机号格式有误！', JuneException::REQ_ERR_PARAMS_INVALID);
	    }
	    $password = $this->encryptPwd($pwd);
	    $fields   = array(
				'name'      => true,
				'sn'        => true,
				'avatar'    => true,
	    		'mob_num'   => true,
	    );
	    
	    $ip_addr = get_client_ip();
	    $criteria = array('enable' => true, 'mob_num'=>$mob_num);
	    //判断是手机号登陆还是用户名登陆
	    if ($this->_tc_name == 'users') {
			$more_fields = array(
				'signature' => true,
				'level'     => true,
				'acc_bal'   => true,
				'gender'    => true,
                'status'    => true,
			);		
			$fields = array_merge($fields,$more_fields);
			
			$criteria['pwd'] =  $password;
			$data['ip_addr'] = $ip_addr;
			$data['act_ts'] = new \MongoDate();
	    } else {
			$fields['account'] 	  = true;
			$criteria['password'] =  $password;
	    	$data['login_ip']	  = $ip_addr;
	    	$data['login_ts']	  = new \MongoDate();
	    }

		$m_tc = $this->_db->getModel($this->_tc_name);
    	$info = $m_tc->findOne($criteria , $fields);
    	
    	if (empty($info)) {
    		throw new JuneException('登录失败请检查用户名和密码！', JuneException::DB_ROW_NOT_EXISTS);
    	}
    	
    	if ($this->_tc_name == 'users') {
        	if ($info['status'] === 'frozen') {
        		throw new JuneException('用户被冻结！', JuneException::DB_ROW_NOT_EXISTS);
        	}
        	unset($info['status']);
    	}
    	
    	//更新登陆ip
    	$res = $m_tc->update(array('_id' => $info['_id']), $data);
    	$info['id'] = $info['_id']->{'$id'};
        unset($info['_id']);
    	foreach ($info as $key => $value) {
  			if(is_array($value)){
  				foreach ($value as $k => $v) {
  					if(is_null($v)) {
  						$info[$key][$k] = '';
  					}
  				}
  			}

            if(!empty($value) && $key == 'avatar'){
                $info['avatar'] = fdfs_image_url($info['avatar']);
            }

  			if(is_null($value)){
  				$info[$key] = '';
  			}
  		}
  		
  		return $info;
	}
	
	/**
     * 重置密码
     * @param String $mob_num
     * @param String $pwd
     * @author 孟振国 &刘富
     */
  	public function resetPwd($mob_num, $pwd) {
  		if(empty($mob_num) || empty($pwd)){
  			throw new JuneException('手机号或密码为空！', JuneException::REQ_ERR_PARAMS_MISSING);
		}

		$criteria = array('mob_num' => $mob_num, 'enable' => true);
		$password = $this->encryptPwd($pwd);
		if ($this->_tc_name == 'users') {
			$data = array('pwd' => $password);
		} else {
			$data = array('password' => $password);
		}
		
		
		$m_tc = $this->_db->getModel($this->_tc_name);
		$update   = $m_tc->update($criteria,$data);

		if(!$update){
			throw new JuneException('密码重置失败，请稍后再试！', JuneException::DB_UPSERT_ERROR);
		}

		$ret = array('msg' => '重置成功! ');
		return $ret;	
  	}
  	
	/**
     * 获取用户基本信息
     * 
     * @author 孟振国&刘富
     */
  	public function getProfile($u_id) {
  		if(empty($u_id)) {
    		throw new JuneException('获取基本信息时参数错误! ', JuneException::REQ_ERR_PARAMS_MISSING);
  		}
  		
  		$id = new \MongoID($u_id);

  		$criteria = array('_id' => $id);
  		$fields   = array(
            '_id'     => false,
  			'name'    => true,
  		    'sn'      => true, 
  		    'mob_num' => true, 
  		    'area'    => true,
  		    'avatar'  => true, 
  		);
  		
  		if ($this->_tc_name == 'users') {
  			$fields['ip_addr']  = true;
  			$fields['area']  	= true;
  		} else {
  			$fields['account']  = true;
  		}
  		
		$m_tc = $this->_db->getModel($this->_tc_name);
  		$info = $m_tc->findOne($criteria, $fields);

  		if(empty($info)){
  			throw new JuneException('请求失败，请稍后再试！', JuneException::DB_ROW_NOT_EXISTS);
  		}

  		foreach ($info as $key => $value) {
  			if(is_null($value)){
  				$info[$key] = '';
  			}

            if(!empty($value) && $key == 'avatar'){
                $info['avatar'] = fdfs_image_url($info['avatar']);
            }

  		}
  		
  		if ($this->_tc_name == 'admins') {
  			// 统计用户数量和累计收入  TODO
  			$info['total_users'] = $m_tc->count(array('enable' => true));
  			$info['total_income'] = 0;
  		}
  		
		return $info;
  	}
  	
	/**
     * 编辑昵称
     * @param String $name 用户名
     * @param String $u_id 用户id
     * @author 孟振国&刘富
     */
  	public function changeName($name, $u_id) {
  		if ( empty($name) || empty($u_id)) {
    		throw new JuneException('编辑昵称时参数错误! ', JuneException::REQ_ERR_PARAMS_MISSING);
  		}

        $u_id = new \MongoId($u_id);
        
  		$criteria = array('_id' => $u_id);	
    	$m_tc  = $this->_db->getModel($this->_tc_name);
		
  		$data     = array('name' => $name);
  		$update   = $m_tc->update($criteria, $data);
		
  		if (!$update) {
    		throw new JuneException('编辑失败，请稍后再试！', JuneException::DB_UPSERT_ERROR);;	
  		}
		
  		$ret = array('msg' => '编辑成功! ');
		return $ret;
  	}

  	/**
     * 编辑头像
     * @param String $u_id 用户id
     * @param $img_file 头像文件
     * @author 孟振国
     */
  	public function uploadAvatar($u_id, $img_file) {
    	if(empty($u_id)){
    		throw new JuneException('编辑头像时参数错误', JuneException::REQ_ERR_PARAMS_MISSING);
    	}

    	if(empty($img_file)){
    		throw new JuneException('头像文件丢失！', JuneException::REQ_ERR_PARAMS_MISSING);
    	}

    	$criteria = array('_id' => new \MongoId($u_id));
    	
    	$m_tc = $this->_db->getModel($this->_tc_name);
		
    	$s_upload = UploadService::getInstance();
    	$upload_ok = $s_upload->upload($img_file);
    	
    	if ($upload_ok) {  			
    		$fid = $s_upload->moveFileToFastDfs(array(120, 120));  					
    	} else {
    		throw new JuneException('图像上传失败！', JuneException::DB_UPSERT_ERROR);
    	}
    	
    	if (empty($fid)) {
    		throw new JuneException('图像上传失败稍后尝试！', JuneException::DB_UPSERT_ERROR);
    	}

    	//更新用户头像
    	$res = $m_tc->update($criteria, array('avatar' => $fid));
    
    	if(empty($res)){
    		throw new JuneException('图像上传失败稍后尝试！', JuneException::DB_UPSERT_ERROR);
    	}
    	
    	$ret = array('msg' => '上传成功！','url' => fdfs_image_url($fid));
		return $ret;

  	}

  	/**
  	 * 用户修改手机
  	 *
  	 *@author 孟振国
  	 */
  	public function changePhone($u_id, $phone) {
  		if (empty($u_id) || empty($phone)) {
  			throw new JuneException('修改手机时参数错误！', JuneException::REQ_ERR_PARAMS_MISSING);
  		}

  		$id = new \MongoId($u_id);
  		$m_tc = $this->_db->getModel($this->_tc_name);
  		
  		if (!$this->checkPhone($phone)) {
  			throw new JuneException('手机格式有误！', JuneException::REQ_ERR_PARAMS_INVALID);
  		}
  		
  		$criteria = array('mob_num' => $phone);
  		$count = $m_tc->count($criteria);
  		if ($count != 0) {
  			throw new JuneException('该手机格已经注册过！！', JuneException::REQ_ERR_PARAMS_INVALID);
  		}
  		
  		$criteria = array('_id' => $id);
  		$data = array('mob_num' => $phone);
  		if ($this->_tc_name == 'admins') {
  			$data['username'] = $phone;
  		}
  		$update = $m_tc->update($criteria,$data);

  		if(!$update) {
  			throw new JuneException('手机号修改失败,请稍后再试！', JuneException::DB_UPSERT_ERROR);
  		}

  		$ret = array('msg' => '编辑成功');
		return $ret;
   	}
   	
 	/**
     * 获取sn（如果全都超过了999999将一直循环）
     * @param  String $prefix
     * @return String 用户的sn
     *
     * @author 王文韬
     */
  	public  function getUserSn($prefix) {
        do {
            $random = mt_rand(1, 99);
            $random_group = $random < 10 ? '0'.$random : $random;
            $key_name = $prefix.$random_group;
            $idx = $this->_db->getMAutoIncIds()->getIncId($key_name);
        } while($idx > 999999);

        $inc_num = str_pad($idx, 6, '0', STR_PAD_LEFT);

        return $random_group.$inc_num;
    }
    
	/**
     * 使用加密规则对字符串加密
     * 
     * @param String $pwd 待加密的明文密码字符串
     * @return String 已加密的密码字符串（数据库存储的是该字符串！！！）
     * 
     */
	public function encryptPwd($pwd) {
		$des3 = new TripleDesService();
        return md5($des3->encrypt(strtolower($pwd)));
	}
	
	/**
	 * 检查手机格式是否正常
	 * @param String $mob_num 待加密的明文密码字符串
     * @return Boolean $ret
     *  
	 */
	public function checkPhone($mob_num){
		if (preg_match("/^1[34578]{1}\d{9}$/",$mob_num)) {
			$ret = true;
		} else {
			$ret = false;
		}
		
		return $ret;
	}
}