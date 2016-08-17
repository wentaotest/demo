<?php namespace June\apps\services;

class MiniClassService {
	static private $_inst;
	
	private function __construct($config) {
		if (!empty($config) && is_array($config)) {
			$this->_config = $config;
		}
	}

	static public function getInstance($config=array()) {
		if (empty(self::$_inst)) {
			self::$_inst = new MiniClassService($config);
		}

		return self::$_inst;
	}
	
	/**
	 * 根据入学年份和学制计算年级名字
	 * 
	 * @param integer $enrolled_year
	 * @param integer $sch_grd
	 * @param integer $len_grd
	 * 
	 * @author 安佰胜
	 */
	public function calcGradeName($enrolled_year, $sch_grd, $len_grd) {
		$curr_year  = intval(date('Y', time()));
		$curr_month = intval(date('m', time()));
		
		$grd_txts = array('一年级','二年级','三年级','四年级','五年级','六年级','七年级','八年级','九年级');
		$grd_shorts = array('一','二','三','四','五','六','七','八','九',);
		$sch_txts = array('小学', '初中', '高中', '九年一贯制', '十二年一贯制');
		$sch_shorts = array('小', '初', '高', '九年制', '十二年制');
		
		if ($curr_month >= 8) {
			$max = $curr_year+1;
		} else {
			$max = $curr_year;
		}
		
		$grd_nm = $grd_txts[$max-$enrolled_year-1];
		
		$ret = array('grd_nm' => $grd_nm, 
					 'grd_num' => $max-$enrolled_year,
				     'full_grd_nm' => $sch_txts[$sch_grd-1].$grd_nm, 
				     'full_grd_nm_s' => $sch_shorts[$sch_grd-1].$grd_shorts[$max-$enrolled_year-1]
		);
		
		return $ret;
	}

	/**
	 * 通过父级的_id或sn判断子级是否存在
	 * 
	 * @param  string|mongoid  $param   判断条件值, 文档的sn或_id的值
	 * @param  string  $child_model_nm  子元素的模型名称
	 * @param  string  $field           子元素中对应的字段名称
	 * @return boolean                  true--存在, false--不存在
	 *
	 * @author 王索
	 */
	public function isExists($param, $child_model_nm, $field_nm) {
		try {
            $id = ($param instanceof \MongoId)? $param: new \MongoId($param);
        } catch (\Exception $e) {
            $sn = $param;
        }
        // 关联的文档的模型
        if( substr($child_model_nm, 0, 4) != 'getM' ) {
        	$m_name = 'getM'.ucfirst($child_model_nm);
        }else{
        	$m_name = $child_model_nm;
        }
        $model = june_get_apps_db_conn_pool()->$m_name();

        // 查询操作
		$criteria            = array('enable' => true);
		$criteria[$field_nm] = isset($id)? $id: $sn;
		$field               = array($field_nm => true);
		$info                = $model->findOne($criteria, $field);

        // 返回结果
        return empty($info)? false: true;
	}
}