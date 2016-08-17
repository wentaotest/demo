<?php

use June\Core\Controller\AppController;

class TestController extends AppController {
    private $_db = null;

    public function actionBefore() {
        parent::actionBefore();
        $this->_db = june_get_apps_db_conn_pool();
    }
    
    public function doIndex() {
    	if(!($this->isAjax())) {
    		$this->display();
    	} else {
    		$old_phone = xp('oldphone');
    		$new_phone = xp('newphone'); 
    		
    		$criteria = array('mob_num' => $old_phone, 'enable' => true);
    		$data  	  = array('mob_num' => $new_phone);
    		
    		$res = $this->_db->getMUsers()->update($criteria, $data);
    		if( $res ) {
    			$out = array('code'=> 0,'info'=>'更改成功！');
    		} else {
    			$out = array('code'=> 1,'info'=>'更改失败！');
    		}
    		echo json_encode($out);
    		exit();
    	}
    }
    
    
    /**
     * 检查该号码是否可用
     * 
     */
    public function doCheckphone () {
    	if($this->isAjax()){
    		$oldphone = xp('oldphone');
    
    		$phone_num = (!empty($oldphone))?xp('oldphone'):xp('newphone');
    		$res = $this->_checkInfo($phone_num);
			if($res){
				if (!empty($oldphone)) {
					$out = 'true';
				} else {
					$out = 'false';
				}
			} else {
				if (!empty($oldphone)) {
					$out = 'false';
				} else {
					$out = 'true';
				}
			}
			echo $out;
			exit;
    	}
    }
    
    /**
     * 检查手机号是否已存在
     * @param String $data 手机号
     * @param Mix			是否可用
     * @return Boolean $ret 存在返回true否则返回false
     *
     */
    private function _checkInfo($data, $enable = true) {
    	$criteria = array();
    
    	$criteria['mob_num'] = $data;

    	$fields = array('mob_num'=>true);
    	if (!empty($enable)) {
    		$fields ['enable']  = true;
    	}
    	$user_info = $this->_db->getMUsers()->findOne($criteria, $fields);
    	//注册的时候有可能该用户已经注册但是已经被禁用
    	if(!empty($user_info)){
    		return true;
    	} else {
    		return false;
    	}
    }
    
}