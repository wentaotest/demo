<?php namespace June\apps\services;

use June\Core\JuneException;
use June\apps\models\MOrders;
use June\apps\services\WxPaymentService;
use June\apps\services\SmsService;
use June\apps\models\MVerificationCodes;
use June\apps\models\MTransApplicationBills;

class ShopRaiderService {
	static private $_inst;
	private $_db;

	private function __construct($config) {
		if (!empty($config) && is_array($config)) {
			$this->_config = $config;
		}
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance($config=array()) {
		if (empty(self::$_inst)) {
			self::$_inst = new ShopRaiderService($config);
		}

		return self::$_inst;
	}
	
	/**
	 * 上线激活活动
	 * 
	 * @param string $lt_id
	 * @param array $lotto_info
	 * @throws JuneException
	 * 
	 * @author Vincent An
	 */
	public function activateNewLotto($lt_id, $lotto_info = array()) {
		if (empty($lt_id)) {
			throw new JuneException('上线活动时参数非法！', JuneException::REQ_ERR_PARAMS_MISSING);
		} else {
			$lt_id = new \MongoId($lt_id);
		}
		
		$my_db_conn   = june_get_mysql_db_conn();
		$m_lottos     = $this->_db->getMLottos();
		$m_lotto_nums = $this->_db->getMLottoNums();
		
		if (empty($lotto_info)) {
			$lotto_info = $m_lottos->findOne(array('_id' => $lt_id),array('sn' => true,'lotto_quota' => true));
		}
		
		try {
			// 开启事务
			$my_db_conn->beginTransaction();
			
			// 插入夺宝进度（MySQL）
			$sql = 'INSERT INTO `lotto_skds` (`lotto_sn`, `lotto_quota`, `curr_bets`, `prog_rate`, `crt_ts`, `mod_ts`) '
			     . 'VALUES ("'.$lotto_info['sn'].'",'.$lotto_info['lotto_quota'].',0,0,'.time().','.time().')';
	
			$res_skds = $my_db_conn->rawInsert($sql);
			
			if (empty($res_skds)) {
				throw new JuneException("上线活动时插入夺宝进度记录失败！");
			}
	
			// 生成夺宝号码（MySQL）
			$res_nums = $m_lotto_nums->genLottoNums($lotto_info['sn'], $lotto_info['lotto_quota']);
			
			if (empty($res_nums)) {
				throw new JuneException("上线活动时生成夺宝号码记录失败！");
			}
			
			// 提交事务
			$my_db_conn->commitTransaction('lottoRollback', $lt_id, 'June\apps\services\ShopRaiderService');
			
			// 更新正在运行的活动列表缓存
			$m_lottos->refreshRunningListCache();
		} catch (JuneException $e) {
			// 执行回滚
			$my_db_conn->rollbackTransaction('lottoRollback', $lt_id, 'June\apps\services\ShopRaiderService');
			
			throw new JuneException("上线活动时发生错误：" . $e->getMessage());
		}
	}
	
	/**
	 * 连期活动创建激活失败后的回滚函数
	 * 
	 * @param MongoId $lt_id
	 * @author Vincent An
	 */
	static public function lottoRollback($lt_id) {
		$m_lottos = june_get_apps_db_conn_pool()->getMLottos();
		
		$criteria = array('_id' => $lt_id);
		
		$m_lottos->delete($criteria, true);
	}
	
	/**
	 * 确定数值B使用的时时彩开奖期号及活动揭晓时间
	 * 
	 * 注意：
	 * 		重庆时时彩开奖时间规则：
	 * 
	 * 		06:00-10:00		销售当天第一期，10:00统一开奖。
	 * 		10:00-21:50		10分钟一期，共72期（24期-95期）
	 * 		22:00-00:00		5分钟一期，共25期（96期-120期）
	 * 		00:05-01:55		5分钟一期,共23期（01期-23期），
	 * 		02:00-06:00		暂停销售，
	 * 
	 * 		全天共计120期。
	 * 		
	 * 		当前时间为18:28，
	 * 		最近一期已开奖时时彩（74期）的开奖时间为18:20，
	 * 		最近一期未开奖时时彩（75期）的开奖时间为18:30，
	 * 		但第三方免费接口会随机延迟3至5分钟后公布开奖信息（实时接口需付费），故活动揭晓时间统一延长7至8分钟，
	 * 		即活动揭晓时间范围是18:37至18:38
	 * 		
	 * 		第三方付费接口会延迟1分钟左右，故活动揭晓时间统一延长80到90秒，获取开奖信息会提前5秒钟，即请求开奖接口的实际时间为开奖后75至85秒时
	 * 		目前已经开通付费接口，管理地址：http://face.apius.cn/?token=2ab9dd5990a8bf7c&verify=2a3f83bc2e8a 自2016年07月07日起服务周期一年
	 * 		
	 * 
	 * @author Vincent An
	 */
	private function _confirmRevealedSkd() {
		// 根据时时彩开奖时间规则，确定下一期时时彩期号及开奖时间，如果在时时彩开奖
		$curr_ts = time();
		
		$hi_time = intval(date("Hi", $curr_ts));
		
		// 不在时时彩开奖时间段，则时时彩期号为0期，即时开奖，30秒后揭晓，数值B结果为0
		if ($hi_time >= 200 && $hi_time < 950) {
			$cqssc_sn    = 0;        // 时时彩期号为0
			$open_ts     = $curr_ts; // 即时开奖
			$revealed_ts = $open_ts; // 即时开奖
			$need_val_b  = false;    // 不需要数值B
		} else {
			
			// 早上10点至晚上22点，每隔10分钟开奖一次
			if ($hi_time >= 950 && $hi_time < 2200) {
				$ref_ts  = strtotime(date("Y-m-d", $curr_ts) . " 09:50:00");
				$ref_sn  = 24;
				$frq_min = 10;
			}
			
			// 晚上22点至凌晨00点，每隔5分钟开奖一次
			if ($hi_time >= 2200 && $hi_time <= 2359) {
				$ref_ts  = strtotime(date("Y-m-d", $curr_ts) . " 22:00:00");
				$ref_sn  = 96;
				$frq_min = 5;
			}
			
			// 凌晨00点至凌晨02点，每隔5分钟开奖一次
			if ($hi_time >= 0 && $hi_time <= 200) {
				$ref_ts  = strtotime(date("Y-m-d", $curr_ts) . " 00:00:00");
				$ref_sn  = 96;
				$frq_min = 5;
			}
			
			$curr_h = intval(date("H", $curr_ts));
			$curr_i = intval(date("i", $curr_ts));
			$curr_ymd = date("Y-m-d", $curr_ts);
				
			$t = floor($curr_i / 10);
			if ($t == 5) {
				$r_h = $curr_h + 1;
				$r_i = "00";
			} else {
				$r_h = $curr_h;
				$r_i = $t * 10 + $frq_min;
			}
				
			$cqssc_sn    = floor(($curr_ts - $ref_ts) / ($frq_min * 60)) + $ref_sn;
			$open_ts     = strtotime("$curr_ymd $r_h:$r_i"); // 时时彩开奖时间
			$revealed_ts = $open_ts + rand(90, 100);         // 付费接口，揭晓时间随机延迟90到100秒揭晓，但获取开奖结果会提前2秒钟
// 			$revealed_ts = $open_ts + rand(7, 8) * 60;       // 免费接口，揭晓时间随机延迟7到8分钟揭晓，但获取开奖结果会提前5秒钟
			$need_val_b  = true;
			
		}
		
		// 将时时彩期号和揭晓时间格式化
		$cqssc_sn = date("Ymd") . str_pad($cqssc_sn, 3, '0', STR_PAD_LEFT);
		$revealed_ts = new \MongoDate($revealed_ts);
		
		// 返回结果
		return array('next_cqssc_sn' => $cqssc_sn, 'revealed_ts' => $revealed_ts, 'need_val_b' => $need_val_b);
	}
	
	/**
	 * 获取计算数值A所需的50条参与记录及数值A
	 * 
	 * @author Vincent An
	 */
	private function _getValueALogs() {
		$now_msec = intval(microtime(true) * 1000); //当前毫秒时间戳
			
		// 连接mysql
		$my_db_conn = $this->_db->getMySqlDBConnInst();
			
		// 获取全站最近的50条参与记录
		$sql = "SELECT `crt_ts`,`bet_cnt`,`user_sn` FROM `lotto_logs` WHERE crt_ts<=".$now_msec." order by crt_ts desc limit 50";
		$logs = $my_db_conn->rawFind($sql, \PDO::FETCH_ASSOC);
		
		
		// 计算数值A，注意：计算50条数据之和不会溢出！
		$val_a = 0;
		$log_pool = array();
		foreach ($logs as &$log) {
			$sec_val  = substr($log['crt_ts'], 0, -3);
			$msec_val = substr($log['crt_ts'],-3);
			
			$val_a += intval(date("His", $sec_val) . $msec_val);
			
			// 查询参与用户昵称
			$user_info = $this->_db->getMUsers()->findOne(array('sn' => $log['user_sn']), array('name' => true));
			
			$rec = array(
					'u_name'   => !empty($user_info['name']) ? $user_info['name'] : '',
					'user_sn'  => $log['user_sn'],
					'crt_ts'   => date('Y-m-d H:i:s', $sec_val) . ".$msec_val",
					'time_num' => intval(date("His", $sec_val) . $msec_val)
			);
			
			$log_pool[] = $rec;
		}
			
		// 返回结果
		return array('val_a' => $val_a, 'log_pool' => json_encode($log_pool,JSON_UNESCAPED_UNICODE));
	}
	
	/**
	 * 揭晓某期活动的幸运号码
	 * 
	 * @param string $lotto_sn
	 * @param integer $val_a
	 * @param integer $val_b
	 * @param integer $lt_quota
	 * 
	 * @author Vincent An
	 */
	public function revealLuckyNum($lotto_sn, $val_a, $val_b, $lt_quota, $third_lt_res = '') {
		$my_db_conn = june_get_mysql_db_conn();
		$m_lottos   = $this->_db->getMLottos();
		$m_users    = $this->_db->getMUsers();
		
		// 计算幸运号码
		$lucky_num = (($val_a + $val_b) % $lt_quota) + 10000001;
		
		// 查询幸运得主
		$sql = "SELECT `user_sn` FROM `lotto_nums` WHERE lotto_num=".$lucky_num." AND lotto_sn=".$lotto_sn;
		$winner = $my_db_conn->rawFindOne($sql, \PDO::FETCH_ASSOC);
		$winner = $this->_db->getMUsers()->findOne(array('sn' => $winner['user_sn']), array('_id' => true, 'mob_num' => true, 'sn' => true));
		
		// 发布幸运号码
		$sql = "UPDATE `lotto_skds` SET third_lotto_res='$third_lt_res', lucky_num=".$lucky_num.", b_value=$val_b, mod_ts=".time()." WHERE lotto_sn=".$lotto_sn;
		$my_db_conn->rawSql($sql);
		
		// 更新夺宝信息
		$criteria = array('sn' => $lotto_sn);
		$data = array('winner' => $winner['_id'], 'lucky_num' => $lucky_num, 'status' => L_STATUS_REVEALED);
		$m_lottos->update($criteria, $data);
		
		// 刷新活动详情、幸运得主等缓存
		$lt_info = $m_lottos->getLottoInfo($lotto_sn);
		$m_users->refreshLottoWinnerCache($lotto_sn);
		$m_lottos->refreshLottoDetailCache(new \MongoId($lt_info['id']));
		$m_lottos->refreshLottoInfoCache($lotto_sn);
		
		// 返回结果
		return array('lucky_num' => $lucky_num, 'winner' => $winner);
	}
	
	/**
	 * 重启揭晓活动结果的任务
	 * 
	 * @param array $f_lottos
	 * 
	 * @author Vincent An
	 */
	public function restartRevealLuckyNums($f_lottos) {
		if (!is_array($f_lottos) || empty($f_lottos)) {
			throw new JuneException('没有需要重启揭晓活动结果的任务！', JuneException::REQ_ERR_PARAMS_INVALID);
		}
		
		$m_lt_skds = june_get_apps_db_conn_pool()->getMLottoSkds();
		
		// 建立与消息队列服务器的连接
		$queue = june_get_queue();
		$queue->connect();
			
		foreach ($f_lottos as $lt) {
			
			// 获得开奖JOB所需参数
			$lt_skd_info = $m_lt_skds->getRevealJobNeededVars($lt['sn']);
			
			// 添加获取时时彩开奖结果任务（并计算幸运号码，揭晓结果）
			$param = array(
					'lotto_sn'       => $lt['sn'],
					'lotto_quota'    => $lt_skd_info['lotto_quota'],
					'a_value'        => $lt_skd_info['a_value'],
					'third_lotto_sn' => $lt_skd_info['third_lotto_sn'],
					'ask_count'      => 1
			);
			
			$job = array('job_class' => 'GetLotteryResJob', 'args' => $param);
			
			$queue->put(0, 0, 10, json_encode($job));
		}
		
		$queue->disconnect();
	}
	
	/**
	 * 订单支付前的检查流程
	 *
	 * @param array $user_info
	 * @param array $order_info
	 * @param integer $method
	 * @param integer $amount
	 * @throws JuneException
	 * @return boolean
	 * @author Vincent An
	 */
	public function checkBeforePayment($user_info, $order_info, $method, $amount) {
		// 如果使用余额支付，则检查用户余额
		if ($method == PAY_METHOD_COINS) {
			if ($user_info['acc_bal'] < $amount) {
				throw new JuneException('夺宝币余额不足！请充值或使用其他方式支付！', JuneException::REQ_ERR_PARAMS_INVALID);
			}
		}
		
		if ($order_info['base_info']['total_bets'] != $amount) {
			throw new JuneException('支付金额错误！', JuneException::REQ_ERR_PARAMS_INVALID);
		}
		 
		// 检查订单状态
		$order_base   = $order_info['base_info'];
		$order_status = $order_base['status'];
		
		if (empty($order_base) 
		|| $order_status == MOrders::ORDER_STATUS_PAID
		|| $order_status == MOrders::ORDER_STATUS_CANCELLED) {
			throw new JuneException('无效订单 ！订单状态：' . MOrders::orderStatusTxt($order_status), JuneException::REQ_ERR_PARAMS_INVALID);
		} else {
			if (time() - $order_base['exp_ts']->{'sec'} > 0) {
				throw new JuneException('订单已过期失效 ！', JuneException::REQ_ERR_PARAMS_INVALID);
			}
		}
		 
		$order_items = $order_info['order_items'];
		
		// 检查活动进度
		foreach ($order_items as $item) {
			$skd = $this->_db->getMLottoSkds()->getLottoSkdBySn($item['lotto_sn']);
			//$alloced_num_cnt = $this->_db->getMLottoNums()->getAllocatedNumCntByLtSn($item['lotto_sn']); // 活动已分配号码的数量
			
			// 如果订单内某活动已结束（状态变成“揭晓中”），则订单失效删除
			$lt_info = $this->_db->getMLottos()->getLottoInfo($item['lotto_sn']);
			if ($lt_info['status'] == L_STATUS_SOLDOUT) {
				$this->_db->getMOrders()->removeOrder($order_base['sn']);
				
				throw new JuneException('订单失效！该订单内活动已结束！', JuneException::REQ_ERR_PARAMS_INVALID);
			}
		
			// 如果订单内某活动剩余人次不足，则订单失效删除
			$left_bets = $skd['lotto_quota'] - $skd['curr_bets'];
			if ($item['bets'] > $left_bets) {
				$this->_db->getMOrders()->removeOrder($order_base['sn']);
				 
				throw new JuneException('订单失效！该订单内活动剩余参与人次不足！', JuneException::REQ_ERR_PARAMS_INVALID);
			}
		}
		 
		return true;
	}
	
	/**
	 * 订单支付前的处理流程
	 *
	 * @param array $order_info
	 * @throws JuneException
	 * 
	 * @author Vincent An
	 */
	public function dealBeforePayment($order_info) {
		$status   = $order_info['base_info']['status'];
		$order_sn = $order_info['base_info']['sn'];
		
		$m_orders = $this->_db->getMOrders();
		
		try {
			// 如果订单处于“支付中”状态，则无需更改活动进度和订单状态，避免重复下单！！！
			if ($status !== MOrders::ORDER_STATUS_PAYING) {
				// 更新订单内活动的进度，不更新活动状态（扣款前将活动数量锁定！！！防止支付扣款过程中被别人抢购）
				$m_orders->updateLottoSkdsInOrder($order_sn);
					
				// 订单状态变更为“支付中”
				$m_orders->update(array('sn' => $order_sn), array('status' => MOrders::ORDER_STATUS_PAYING));
			}
		} catch (JuneException $e) {
			throw new JuneException($e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * 订单支付后的处理流程
	 *
	 * @param array $user_info
	 * @param array $order_info
	 * @param integer $method
	 * @param integer $amount
	 * @param string $receipt
	 * @throws JuneException
	 * @author Vincent An
	 */
	public function dealAfterPayment($user_info, $order_info, $method, $amount, $receipt) {
		$s_shop_raider = ShopRaiderService::getInstance();
		$my_db_conn    = june_get_mysql_db_conn();
		$m_lottos      = $this->_db->getMLottos();
		$m_order_items = $this->_db->getMOrderItems();
		$m_exp_logs    = $this->_db->getMExpLogs();
		$m_lotto_nums  = $this->_db->getMLottoNums();
		$m_orders      = $this->_db->getMOrders();
		 
		// 整理参数
		$user_sn     = $user_info['sn'];
		$order_sn    = $order_info['base_info']['sn'];
		$acc_bal     = $method == PAY_METHOD_COINS ? $user_info['acc_bal'] - $amount : $user_info['acc_bal'];
		$order_items = $order_info['order_items'];
		
		// 查询订单内所有活动已分配号码数量、活动所需人次
		$alloced_cnt = array();
		foreach ($order_items as &$lotto) {
			$lt_sn = $lotto['lotto_sn'];
			$lt_id = $lotto['lotto_id'];
			
			$alloced_cnt[$lt_sn] = $m_lotto_nums->getAllocatedNumCntByLtSn($lt_sn);
			
			$lotto['lotto_info'] = $m_lottos->getLottoDetail($lt_id);
		}
		 
		// 创建支出记录（MongoDB）
		$m_exp_logs->createExpLog($user_sn, $order_sn, $method, $amount, $receipt, $acc_bal);
		
		// 更新订单状态
		$m_orders->update(array('sn' => $order_info['base_info']['sn']), array('status' => MOrders::ORDER_STATUS_PAID));
		 
		// 异常回滚函数的参数列表
		$rollback_params = array('user_info' => $user_info, 'order_info' => $order_info, 'pay_method' => $method);
		
		try {
			// 开启事务
			$my_db_conn->beginTransaction();
			 
			foreach ($order_items as $lotto) {
				$lt_sn   = $lotto['lotto_sn'];
				$lt_id   = $lotto['lotto_id'];
				$lt_info = $lotto['lotto_info'];
	
				// 分配夺宝号码（MySQL）
				$alloc_sn = $m_lotto_nums->allocLottoNums($user_sn, $lt_sn, $lotto['bets']);
				
				if (empty($alloc_sn)) {
					throw new JuneException('夺宝号码分配时发生错误！');
				}
				 
				// 创建夺宝日志（MySQL）
				$res_p_log = $this->_db->getMLottoLogs()->createParticipationLog($user_info['sn'], $lt_sn, $alloc_sn);
				
				if (empty($res_p_log)) {
					throw new JuneException('夺宝日志创建时发生错误！');
				}
	
				// 检查活动状态是否需要变更（活动已分配号码的数量是否达到总需参与人次）
				$alloced_num_cnt  = $alloced_cnt[$lt_sn]; // 已分配
				$to_alloc_num_cnt = $lotto['bets'];       // 待分配（因为事务尚未提交）
				 
				// 只有分配号码数量等于活动总需参与人次时，活动状态才能变更为“揭晓中”！
				if ($alloced_num_cnt + $to_alloc_num_cnt == $lt_info['lotto_quota']) {
					$s_shop_raider->changeToSoldOut($lt_info); // 变更活动状态、进度
				}
				 
				// 刷新活动信息缓存
				$m_lottos->refreshLottoInfoCache($lt_sn);
				$m_lottos->refreshLottoDetailCache(new \MongoId($lt_id));
			}
			 
			// 提交事务
			$my_db_conn->commitTransaction('orderRollback', $rollback_params, 'June\apps\services\ShopRaiderService');
	
		} catch (JuneException $e) {
			// 执行回滚
			$my_db_conn->rollbackTransaction('orderRollback', $rollback_params, 'June\apps\services\ShopRaiderService');
			
			throw new JuneException('支付后处理过程发生错误：' . $e->getMessage(), $e->getCode());
		}
		 
	}
	
	/**
	 * 将活动状态变更为“已售罄”，同时确定活动揭晓时间、计算数值A
	 *
	 * @param array $lotto_info
	 * @return array
	 *
	 * @author Vincent An
	 */
	public function changeToSoldOut($lotto_info) {
		$lt_sn  = $lotto_info['lotto_sn'];
		$now_ts = time();
	
		// 确定使用的时时彩期号及活动揭晓时间
		$revealed_skd = $this->_confirmRevealedSkd();
	
		// 获取计算数值A所需的50条参与记录及数值A
		$val_a_logs = $this->_getValueALogs();
	
		// 更新活动进度中的数值A记录、时时彩期号、活动揭晓时间
		$val_a    = $val_a_logs['val_a'];
		$log_pool = $val_a_logs['log_pool'];
	
		$next_cqssc_sn = $revealed_skd['next_cqssc_sn'];
		$revealed_ts   = $revealed_skd['revealed_ts'];
	
		// 更新活动进度
		$m_l_skds = $this->_db->getMLottoSkds();
		$m_l_skds->updateValueA($lt_sn, $val_a, $log_pool, $now_ts, $next_cqssc_sn, $revealed_ts->{'sec'});
	
		// 更新活动信息
		$d = array('status' => L_STATUS_SOLDOUT, 'sold_out_ts' => new \MongoDate($now_ts),'revealed_ts' => $revealed_ts);
		$this->_db->getMLottos()->updateLottoInfo($lt_sn, $d);
	
		// 建立与消息队列服务器的连接
		$queue = june_get_queue();
		$queue->connect();
	
		// 如果需要数值B，则发起获取时时彩任务，否则直接开奖（揭晓结果）
		if ($revealed_skd['need_val_b']) {
				
			// 添加获取时时彩开奖结果任务（并计算幸运号码，揭晓结果）
			$param = array(
					'lotto_sn'       => $lt_sn,
					'lotto_quota'    => $lotto_info['lotto_quota'],
					'a_value'        => $val_a,
					'third_lotto_sn' => $next_cqssc_sn,
					'ask_count'      => 1
			);
				
			$job = array('job_class' => 'GetLotteryResJob', 'args' => $param);
				
			// 比活动揭晓时间提前2秒获取时时彩开奖结果
			$delay_ts = $revealed_skd['revealed_ts']->{'sec'} - time() - 2;
				
			$queue->put(0, $delay_ts, 10, json_encode($job));
				
		} else {
			$this->revealLuckyNum($lt_sn, $val_a, 0, $lotto_info['lotto_quota']);
		}
	
		// 如果是多期活动，添加上线新活动任务
		if ($lotto_info['is_series'] && ($lotto_info['sub_lt_cnt'] > 0)) {
			$lt_id = $lotto_info['_id']->{'$id'};
				
			// parent_id为所有子活动的父级活动id，template_id为创造子活动的模板活动
			$param = array(
					'template_id' => $lt_id,
					'parent_id'   => !empty($lotto_info['parent_id']) ? $lotto_info['parent_id'] : $lt_id,
			);
	
			// 创建任务
			$job = array('job_class' => 'AutoOpenLottoJob', 'args' => $param);
	
			$queue->put(0, 0, 5, json_encode($job));
		}
	
		// 断开与消息队列服务器的连接
		$queue->disconnect();
		
		// 向中心管理服务器提出转账申请！！！异步请求
		$this->applyTransfer($lt_sn);
	
		// 返回揭晓信息
		return array_merge($revealed_skd, $val_a_logs);
	}
	
	/**
	 * 支付扣款后分配号码失败的回滚操作（主要针对MongoDB）
	 *
	 * @param array $roll_params
	 *
	 * @author Vincent An
	 */
	public function orderRollback($roll_params) {
		extract($roll_params);
		
		$db_conn = june_get_apps_db_conn_pool();
	
		$order_sn = $order_info['base_info']['sn'];
	
		// 执行退款操作，同时更新用户支出记录（退款）（第三方支付使用异步操作）
		switch ($pay_method) {
			case PAY_METHOD_COINS:
				$user_sn  = $user_info['sn'];
				$amount   = $order_info['base_info']['total_bets'];
	
				$db_conn->getMUsers()->refundToBalance($user_sn, $amount, $order_sn);
				break;
			case PAY_METHOD_WECHAT:
				// 微信退款（异步操作）
				//查询最新的一条微信订单号
				$fields = array('receipt' => true,'amount' => true);
				$criteria = array('order_sn' => $order_sn,'user_sn' => $user_info['sn'],'type' => PAY_METHOD_WECHAT);
				$sort = array('crt_ts' => -1);
				
				$expLogs = $db_conn->getMExpLogs()->find($criteria, $fields, $sort, 0, 1);
	
				//查询微信交易状态
				$s_wxpay = new WxPaymentService();
				$trade_state = $s_wxpay->wxOrderQuery($expLogs[0]['receipt'], $order_sn);
	
				if($trade_state){
					// 建立与消息队列服务器的连接
					$queue = june_get_queue();
					$queue->connect();
	
					// 添加微信退款job
					$param = array(
							'transaction_id' => $expLogs[0]['receipt'],
							'order_sn' => $order_sn,
							'user_sn' => $user_info['sn'],
							'out_refund_no' => strval(time().getCode(8)),
							'total_fee' => $expLogs[0]['amount'],
							'refund_fee' => $expLogs[0]['amount'],
							//'total_fee' => $expLogs[0]['amount'] * 0.1, //临时兑换规则，上线时不能使用！！
							//'refund_fee' => $expLogs[0]['amount'] * 0.1 //临时兑换规则，上线时不能使用！！
					);
	
					$job = array('job_class' => 'WxRefundJob', 'args' => $param);
						
					$queue->put(0, 0, 5, json_encode($job));
				}
	
				break;
			case PAY_METHOD_ALIPAY:
				// 支付宝退款 即时到账有密退款接口需要人工干预
				//查询用户余额
				$acc_bal = $db_conn->getMUsers()->getBalanceByUserSn();
				
				//查询最新一条支付宝交易号
				$fields = array('receipt' => true,'amount' => true);
				$criteria = array('order_sn' => $order_sn,'user_sn' => $user_info['sn'],'type' => PAY_METHOD_ALIPAY);
				$sort = array('crt_ts' => -1);
				
				$expLogs = $db_conn->getMExpLogs()->find($criteria, $fields, $sort, 0, 1);
				
				//$amount = $expLogs['amount'] * 0.1; //临时兑换规则，上线时不能使用！！ 
				$amount = $expLogs['amount'];
				
				//生成退款单数据
				$db_conn->getMRefundLogs()->createRefundLog($user_info['sn'], $order_sn, REFUND_METHOD_ALIPAY, $amount, null, $acc_bal, $expLogs[0]['receipt'], 0);
				break;
		}
	
		// 撤销夺宝活动状态、活动进度、订单状态的修改
		$m_lottos  = $db_conn->getMLottos();
		$m_lt_skds = $db_conn->getMLottoSkds();
		$m_orders  = $db_conn->getMOrders();
	
		foreach ($order_info['order_items'] as $item) {
			$lt_sn = $lotto['lotto_sn'];
			$lt_id = $lotto['lotto_id'];
				
			// 撤销活动状态修改
			$criteria = array('sn' => $lt_sn);
			$data = array('sold_out_ts' => null, 'revealed_ts' => null, 'status' => L_STATUS_RUNNING);
			
			$m_lottos->update($criteria, $data);
			
			// 撤销活动进度修改
			$m_lt_skds->updateLottoSkd($lt_sn, -$item['bets']);
				
			// 还原缓存
			$m_lottos->refreshLottoInfoCache($lt_sn);
			$m_lottos->refreshLottoDetailCache(new \MongoId($lt_id));
		}
	
		// 撤销订单状态
		$m_orders->update(array('sn' => $order_info['base_info']['sn']), array('status' => MOrders::ORDER_STATUS_PAY_FAILED));
	
		// 刷新客户端运行中的活动列表缓存
		$m_lottos->refreshRunningListCache();
	}
	
	/**
	 * 向中心管理服务器发起转账申请
	 * 
	 * @param string $lt_sn
	 * 
	 * @author Vincent An
	 */
	public function applyTransfer($lt_sn) {
		$m_lottos  = $this->_db->getMLottos();
		$m_t_bills = $this->_db->getMTransApplicationBills();
		
		try {
			// 获取活动信息
			$criteria = array('sn' => $lt_sn);
			$fields = array('product' => true, 'lotto_quota' => true, 'sn' => true, 'title' => true,
					'sold_out_ts' => true, 'revealed_ts' => true);
			$lt_info = $m_lottos->findOne($criteria, $fields);
			
			// 生成转账申请单序号
			$bill_sn = $m_t_bills->genBillSn($lt_sn);
			
			// 整理转账单参数
			$lt_quota       = $lt_info['lotto_quota'];
			$brokerage_rate = C('web_conf', 'brokerage_rate');
			$brokerage      = ceil($lt_quota * $brokerage_rate);
			$trans_amount   = $lt_quota - $brokerage;
			
			// 生成转账申请单
			$bill_data = array(
					'bill_sn'        => $bill_sn,
					'lt_sn'          => $lt_sn,
					'lt_title'       => $lt_info['title'],
					'product'        => $lt_info['product'],
					'lt_quota'       => $lt_quota,
					'brokerage'      => $brokerage,
					'brokerage_rate' => $brokerage_rate,
					'trans_amount'   => $trans_amount,
					'pay_method'     => MTransApplicationBills::PLATFORM_WXPAY,
					'sold_out_ts'    => $lt_info['sold_out_ts'],
					'revealed_ts'    => $lt_info['revealed_ts'],
					'status'         => MTransApplicationBills::TAB_STATUS_PENDING,
					'crt_ts'         => new \MongoDate(time()),
					'mod_ts'         => new \MongoDate(time()),
					'enable'         => true,
			);
			
			$bill_id = $m_t_bills->insertOneGetId($bill_data);
			
			// 建立与消息队列服务器的连接
			$queue = june_get_queue();
			$queue->connect();
			
			// 添加转账请求任务
			$job_params = array('bill_id' => $bill_id);
			
			$job = array('job_class' => 'TransferJob', 'args' => $job_params);
			
			$queue->put(0, 2, 10, json_encode($job));
		} catch (JuneException $e) {
			throw new JuneException($e->getMessage(), $e->getCode());
		}
		
		return true;
	}

	/**
	 * 生成奖品发货单和发货单条目
	 *
	 * @author 孟生伟
	 */
	public function CreateInvoices($lotto_sn) {
		//查询活动信息
		$criteria = array('sn' => $lotto_sn,'status' => 3);
		$fields   = array('winner' => true,'product' => true,'p_name' => true);
		$lt_info  = $this->_db->getMLottos()->findOne($criteria,$fields);

		//根据获奖得主获取中奖用户的详细信息
		$u_criteria = array('_id' => $lt_info['winner']);
		$u_fields   = array('cotact' => true,'sn' => true);
		$u_info  = $this->_db->getMUsers()->findOne($u_criteria,$u_fields);
		
		//获得自增发货单序号
		$curr_year = date('Y');
		$date = date('md');
		$year = $this->_db->getMLottos()->year[$curr_year];
		
		$key_name = 'invoices'.$year.$date;
		$idx = $this->_db->getMAutoIncIds()->getIncId($key_name);
		
		//生成发货单序号
		$sn = $year . $date . str_pad($idx, 4, '0', STR_PAD_LEFT);

		//组合发货单数据
		$data = array(
				'sn'          => $sn,
				'item_cnt'    => 1,
				'user_sn'     => $u_info['sn'],
				'recipient'   => $u_info['cotact'],
				'express_com' => '',
				'express_sn'  => '',
				'status'      => 0,
				'crt_ts'      => new \MongoDate(),
				'mod_ts'      => new \MongoDate(),
				'crt_by'      => '',
				'mod_by'      => '',
				'enable'      => true,
			);
		$res = $this->_db->getMInvoices()->insertOneGetId($data);
		//如果写入成功就生成发货单条目
		if ($res) {
			//写入发货单条目
			$data = array(
					'invoice_sn' => $sn,
					'lotto_sn'   => $lotto_sn,
					'prod_id'    => $lt_info['product'],
					'prod_nm'    => $lt_info['p_name'],
					'crt_ts'     => new \MongoDate(),
					'mod_ts'     => new \MongoDate(),
					'enable'     => true
				);
			//写入发货单条目
			$result = $this->_db->getMInvoiceItems()->insertOneGetId($data);
			if ($result) {
				return true;
			} else {
				return false;
			}

		} else {
			return false;
		}
	}
	
	/**
	 * 抓取时时彩结果
	 *
	 * 付费接口管理界面：http://face.apius.cn/?token=2ab9dd5990a8bf7c&verify=2a3f83bc2e8a 自2016年07月07日起服务周期一年
	 * 请求超过30次即绑定IP，一天限制3个IP使用
	 *
	 * @param string $third_lt_sn
	 * @return array
	 *
	 * @author Vincent An
	 */
	public function graspThirdLottoResult($third_lt_sn) {
		$open_code = '0,0,0,0,0';
		$b_value   = 0;
		
		$url = 'http://c.apiplus.net/newly.do?token=2ab9dd5990a8bf7c&code=cqssc&rows=20&format=json'; // 付费接口
		// 		$url = 'http://f.apiplus.cn/cqssc-20.json?_='.time(); // 免费接口
		
		// 请求参数设置
		$context = stream_context_create(array(
				'http' => array(
						'timeout' => 5 //超时时间，单位为秒
				)
		));
		
		$res_json = file_get_contents(urldecode($url), false, $context);
		
		// 如果返回结果为空，则将时时彩结果置为'0,0,0,0,0'，B值置为'0'
		if (!empty($res_json)) {
			$res_arr  = json_decode($res_json, true); // 返回最近的20条开奖数据，也可以按照日期查询当天全部结果（视情况决定是否使用）
				
			if (!empty($res_arr['data']) && is_array($res_arr['data'])) {
				foreach($res_arr['data'] as $lt){
					if($lt['expect'] == $third_lt_sn){
						$open_code = $lt['opencode']; //开奖号码，形如：1,3,6,2,4
						$b_value   = intval(str_replace(',', '', $open_code));
						break;
					}
				}
			}
		}
		
		// 返回结果
		return array('third_lotto_res' => $open_code, 'b_value' => $b_value);
	}
	
	/**
	 * 生成兑换码，通知用户中奖信息
	 *
	 * @param string $lotto_sn 活动编号
	 *
	 * @author 李优
	 */
	public function informToWinner($lotto_sn) {
		//查询活动信息
		$criteria = array('sn' => $lotto_sn);
		$fields = array('winner' => true,'lucky_num' => true,'p_name' => true);
		$lt_info = $this->_db->getMLottos()->findOne($criteria, $fields);
		
		//查询用户信息
		$criteria = array('_id' => $lt_info['winner']);
		$fields = array('mob_num' => true);
		$user_info = $this->_db->getMUsers()->findOne($criteria, $fields);
		
		// 随机生成6位数字兑换码
		$code      = rand(100000, 999999);
		$code_type = MVerificationCodes::CODE_TYPE_EXCHANGE;
		
		// 保存验证码信息
		$exp_ts = new \MongoDate(time()+ 3600 * 24 * 7);
			
		$m_verification_codes = $this->_db->getMVerificationCodes();
		$ret = $m_verification_codes->createLog(strval($code),$lotto_sn,$user_info['mob_num'],$code_type,1,$exp_ts);
		
		// 推送消息
		$this->_db->getMPushMsgs()->pushWinnerMsg($lotto_sn,$user_info['mob_num'],$code);
		
		// 发送短信
		$sms = SmsService::getInstance();
		$content = array('lotto_sn' => $lotto_sn,'lucky_num' => $lt_info['lucky_num'],'p_name' => $lt_info['p_name'],'code' => $code);
		$msg = $sms->createLuckyMessage($user_info['mob_num'],$content);
		$res = $sms->send($msg);
		
		if ($res['error'] !== 0) {
			$msg_content = "业务类型：[".MVerificationCodes::codeTypeTxt($code_type)."] 验证码：[$code]";
			$m_daemon_logs = $this->_db->getMDaemonLogs();
			$m_daemon_logs->createShortMsgFailedLog($user_info['mob_num'], $msg_content, $res['error'], $res['msg']);
		}
		
		return $code;
	}
	
	/**
	 * 通知用户退款信息
	 *
	 * @param string $user_sn 用户编号
	 * @param string $order_sn 订单编号
	 * @param integer $amount 退款金额
	 *
	 * @author 李优
	 */
	public function informRefund($user_sn,$order_sn,$amount) {
		//查询用户信息
		$criteria = array('sn' => $user_sn);
		$fields = array('mob_num' => true);
		$user_info = $this->_db->getMUsers()->findOne($criteria, $fields);
		
		// 推送退款消息给用户
		$this->_db->getMPushMsgs()->pushRefundMsg($user_sn,$order_sn,$amount);
	
		$content = $order_sn.':'.$amount;
		//发送短信
		$sms = SmsService::getInstance();
		$msg = $sms->createMessage($user_info['mob_num'],$content,'refund');
		$res = $sms->send($msg);
		
		$code_type = MVerificationCodes::CODE_TYPE_REFUND;
	
		$ret = '';
		if ($res['error'] === 0) {
			//保存信息
			$m_verification_codes = $this->_db->getMVerificationCodes();
			$ret = $m_verification_codes->createLog(strval($amount),$order_sn,$user_info['mob_num'],6,2,null);
		} else {
			$msg_content = "业务类型：[".MVerificationCodes::codeTypeTxt($code_type)."] 退款数据：[$content]";
			$m_daemon_logs = $this->_db->getMDaemonLogs();
			$m_daemon_logs->createShortMsgFailedLog($user_info['mob_num'], $msg_content, $res['error'], $res['msg']);
		}
	
		return true;
	}
	
	/**
	 * 修复数据非法的活动（正在揭晓）
	 * 
	 * 规则：
	 * 		1、lottos记录的状态与lotto_skds记录的数据不匹配，如：lottos记录表明活动已售罄，但skds记录售出进度却不是100；
	 * 		2、lotto_skds记录与lotto_nums记录的数据不一致（付款时锁进度的情况除外），如：nums记录表明号码已经完全分配，但skds记录售出进度不是100；
	 * 
	 * 除上述规则以外的情况不在此处考虑范围内，如：用户扣钱但不分配号码（暂时需人工核查，后期可考虑程序排查）
	 * 
	 * @author Vincent An
	 */
	public function repairDataInvalidLottos() {
		$m_lottos  = $this->_db->getMLottos();
		$m_lt_skds = $this->_db->getMLottoSkds();
		$m_lt_nums = $this->_db->getMLottoNums();
		
		// 获取所有标记为售罄的活动列表
		$sold_out_lottos = $m_lottos->getSoldOutLottos();
		
		if (is_array($sold_out_lottos) && !empty($sold_out_lottos)) {
			foreach ($sold_out_lottos as $s_lt) {
				$lt_sn    = $s_lt['sn'];
				$lt_quota = $s_lt['lotto_quota'];
				
				$lt_skd = $m_lt_skds->getLottoSkdBySn($lt_sn);
				
				// 如果标记为售罄的活动售出进度不是100，则该数据需要修复
				if (!empty($lt_skd) && $lt_skd['prog_rate'] !== 100) {
					// 获取已经分配的号码数量（订单已支付）
					$allocated_num_cnt = $m_lt_nums->getAllocatedNumCntByLtSn($lt_sn);
					
					// 获取正在分配的号码数量（订单支付中）
					$allocating_num_cnt = $m_lt_nums->getAllocatingNumCntByLtSn($lt_sn);
					
					$fixed_curr_bets = $allocated_num_cnt + $allocating_num_cnt;
					$fixed_prog_rate = ($lotto_quota === $fixed_curr_bets) ? 100 : floor(($fixed_curr_bets / $lotto_quota) * 100);
					
					// 修复活动进度
					$m_lt_skds->doUpdateLottoSkd($lt_sn, $fixed_curr_bets, $fixed_prog_rate);
					
					// 修复活动状态
					if ($fixed_prog_rate !== 100) {
						$m_lottos->updateLottoInfo($lt_sn, array('status' => L_STATUS_RUNNING, 'sold_out_ts' => null));
					}
				}
			}
		}
	}
	
	/**
	 * 清除缓存数据
	 * 
	 * @return integer
	 * 
	 * @author 王文韬 & Vincent An
	 */
	public function flushCache() {
		// 要删除的键值前缀
		$key_pre = array(CK_LOTTO_INFO, CK_LOTTO_DETAIL, CK_RUNNING_LOTTOS_LIST, CK_PRODUCT_INFO, CK_PRODUCT_DESC,
				CK_LOTTO_CATE_NAMES, CK_LOTTO_CATE_LIST, CK_LOTTO_ZONE_NAMES, CK_LOTTO_ZONE_LIST,
				CK_LOTTO_CALC_RESULT, CK_WINNER_INFO,
		);
		
		$redis = june_get_cache_client()->getRedis();
		$count = 0;
		foreach ($key_pre as $pre) {
			$i = 0;
			$keys = $redis->keys($pre.'*');
			foreach ($keys as $key) {
				$i += $redis->delete($key);
			}
		
			$count += $i;
		}
		
		return $count;
	}
	
}