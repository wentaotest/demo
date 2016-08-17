<?php namespace June\apps\services;

class DeviceService {

    private static $_inst = null;
    private $_db = null;

    private function __construct() {
        $this->_db = june_get_apps_db_conn_pool();
    }

    public static function getInstance() {
        if(self::$_inst == null) {
            self::$_inst = new DeviceService();
        }
        return self::$_inst;
    }

    /**
     * 更新教室的相关数据
     * 
     * @param  string $clsr_sn 教室的sn
     * @param  string $dev_sn  设备的sn
     * @param  string $type    操作类型，bind--绑定，unbind--解绑
     * @return boolean         操作结果
     *
     * @author 王索
     */
    public function updateClsrInDev($clsr_sn, $dev_sn, $type = 'bind') {

        $m_clsr   = $this->_db->getMClassrooms();
        $data     = array(); //要更新的数据
        $now_time = new \MongoDate( time() ); //当前时间

        // 绑定时的操作
        if($type == 'bind') {
            // 查询初次绑定时间,若为空则说明是初次绑定，则更新数据，否则不更新数据
            $criteria = array('sn' => $clsr_sn, 'enable' => true);
            $field    = array('bind_ts' => true);
            $info     = $m_clsr->findOne($criteria, $field);
            if( empty($info['bind_ts']) ) {
                $data['bind_ts'] = $now_time;
            }
            $data['dev_sn'] = $dev_sn;
            $data['mod_ts'] = $now_time;

        }else if($type == 'unbind') {
            // 解绑时的操作
            $criteria = array('sn' => $clsr_sn, 'enable' => true);
            $data     = array(
                'dev_sn' => null,
                'mod_ts' => $now_time,
                );
        }else{
            throw new \June\Core\JuneException\JuneException('操作类型不合法！');
        }

        return $m_clsr->update($criteria, $data);
    }

    /**
     * 教室是否存在
     * 
     * @param  string $clsr_sn 教室的sn
     * @return boolean         true--存在，false--不存在
     *
     * @author 王索
     */
    public function clsrExists($clsr_sn) {
        $criteria = array('sn' => $clsr_sn, 'enable' => true);
        $field = array('_id' => true);
        $info = $this->_db->getMClassrooms()->findOne($criteria, $field);
        return empty($info)? false: true;
    }
}