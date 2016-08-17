<?php

use June\Core\Controller\AppController;
use June\apps\services\WxUserService;
use June\apps\models\MPayeeAccounts;

class AccountController extends AppController {
    private $_db = null;

    public function actionBefore() {
        parent::actionBefore();
        $this->_db = june_get_apps_db_conn_pool();
    }
    
    /**
     * 收款账号列表
     * 
     * @author Vincent An
     */
    public function doIndex() {
    	$app_id = xr('app_id');
    	
    	if (!empty($app_id)) {
    		$m_p_accounts = $this->_db->getMPayeeAccounts();
    		
    		$criteria = array('app_id' => $app_id);
    		$account_list = $m_p_accounts->find($criteria);
    		
    		$this->assign('account_list', $account_list);
    	}
    	
    	$this->display();
    }
    
    /**
     * 添加收款账号
     * 
     * @author Vincent An
     */
    public function doAdd() {
    	$app_id  = xr('app_id');
    	$token   = xr('token');
    	$open_id = xr('open_id');
    	$name    = xr('name');
    	$err_msg = xr('err_msg');
    	
    	if ($this->isPost()) {
    		$s_wx_user = WxUserService::getInstance();
    		$m_p_accounts = $this->_db->getMPayeeAccounts();
    		 
    		$payee_info = $s_wx_user->getFollowerUserInfo($open_id);
    		 
    		$res = $m_p_accounts->addAccount($app_id, $open_id, $name, MPayeeAccounts::PLATFORM_WXPAY);
    		 
    		if ($res) {
    			$this->redirect('account.index', array('app_id' => $app_id));
    		} else {
    			$this->redirect('account_add', array('err_msg' => '添加失败'));
    		}
    	} else {
    		$this->assign('err_msg', $err_msg);
    		$this->display();
    	}
    }
    
}