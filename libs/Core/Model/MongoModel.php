<?php namespace June\Core\Model;

use June\Core\Model;
use June\Core\JuneException;

/**
 * MongoDB的Model父类
 *
 * @author 安佰胜
 */
class MongoModel extends Model {
    
	/**
	 * 构造函数
	 * 
	 * @param \MongoDbConn $db_conn_inst
	 * @param string $tc_name
	 * 
	 * @author 安佰胜
	 */
    public function __construct($db_conn_inst, $tc_name) {
		$this->_db_conn_inst = $db_conn_inst;
        $this->_tc_name = $tc_name;
    }
    
    /**
     * 真正执行连接数据库（延迟连接）
     * 
     * @param boolean $master
     * 
     * @author 安佰胜
     */
    private function _conn_db($master = true) {
    	$tc_name = $this->_tc_name;
    	
    	$this->_db_serv = $this->_db_conn_inst->getDbServConn($master);
    	$this->_db = $this->_db_conn_inst->getDb($master);
    	$this->_tc = $this->_db->$tc_name;
    }
    
    /**
     * 成员方法调用的魔术方法
     * 
     * @param string $method
     * @param array $parameter
     * 
     * @author 安佰胜
     */
    public function __call($method, $parameter) {
        $start = microtime(true) * 1000;

        $result = call_user_func_array(array($this, $method), $parameter);

        $elapsed_time = microtime(true) * 1000 - $start;

        if ($elapsed_time > 500) {
            error_log('MongoDB Operation Time : ' . $elapsed_time . ' ms [ ' . get_class() . '->' . $method . ' ]');
        }
    }
    
    /**
     * 获取集合名字
     * 
     * @return string $coll_name
     * 
     * @author 安佰胜
     */
    public function getCollectionName() {
       return $this->_tc_name;
    }
    
    /**
     * 生成缓存记录对应的键名
     * 
     * @param array $criteria
     * @param array $fields
     * @return string $key
     * 
     * @author 安佰胜
     */
    public function makeCacheKey($criteria, $fields = array()) {
    	if (empty($criteria)) {
    		throw new JuneException('生成缓存键名时，查询条件不能为空！');
    	}
    	
    	$c_str = json_encode($criteria);
    	$f_str = json_encode($fields);
    	
    	$tc_nm = $this->getCollectionName();
    	
    	return 'temp_' . $tc_nm . '_' . md5($c_str . $f_str);
    }
    
    /**
     * 插入数据
     * 
     * @param  array $values 插入的数据
     * @return boolean 插入是否成功
     * 
     * @author 安佰胜
     */
    public function insert($values) {
        // 检查是否为批量插入（小批量）
        $batch = true;
        
        foreach ($values as $value) {
            if (!is_array($value)) {
                $batch = false; break;
            }
        }
        
        $values = !$batch ? array($values) : $values;
        
        // 连接数据库主节点
        $this->_conn_db(true);
        
        $result = $this->_tc->batchInsert($values);
        
        return ((int) $result['ok'] == 1);
    }
    
    /**
     * 插入一条记录并返回文档MongoId
     * 
     * @param array $value 插入的数据
     * @return mixed
     * 
     * @author anbaisheng
     */
    public function insertOneGetId(array $value) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        // 插入数据
        $result = $this->_tc->insert($value);
        
        if ((int) $result['ok'] == 1) {
            return $value['_id']->{'$id'};
        }
        
        return false;
    }
    
    /**
     * 查找数据记录列表
     * 
     * @param  array   $criteria 查找条件
     * @param  array   $fields 返回字段
     * @param  array   $sort_by 排序方式
     * @param  integer $skip 跳过记录的数量
     * @param  integer $limit 限制返回记录的数量
     * @return mixed 查找结果
     * 
     * @author anbaisheng
     */
    public function find($criteria, $fields = array(), $sort_by = array(), $skip = NULL, $limit = NULL) {
    	// 连接数据库主节点
    	$this->_conn_db(false);
    	
    	try {
    		if (!empty($fields)) {
    			$cur = $this->_tc->find($criteria, $fields);
    		} else {
    			$cur = $this->_tc->find($criteria);
    		}
    		
    		if (!empty($sort_by)) {
    			$cur = $cur->sort($sort_by);
    		}
    		
    		if((!empty($skip) || $skip == 0) && !empty($limit)){
    			$cur = $cur->skip($skip)->limit($limit);
    		}
    	
	        $ret = array();
	        foreach ($cur as $p) {
	            $ret[] = $p;
	        }
        
        } catch (\MongoException $e) {
        	throw new JuneException($e->getMessage(), $e->getCode());
        }
        
        return $ret;
    }
    
    /**
     * 查找单条数据记录
     * 
     * @param array $criteria 查找条件
     * @param array $fields 返回字段
     * @return mixed 查找结果
     * 
     * @author 安佰胜
     */
    public function findOne($criteria, $fields = array()) {
    	// 连接数据库主节点
    	$this->_conn_db(false);
    	
        return $this->_tc->findOne($criteria, $fields);
    }
    
    /**
     * 查找并修改某条记录（原子操作）
     * 
     * @param  array $criteria 查找条件
     * @param  array $data 更新数据内容
     * @param  array $fields 返回字段
     * @param  array $options 选项
     * @return mixed 查找结果
     */
    public function findAndModify($criteria, $data = array(), $fields = array(), $options = array()) {        
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
    	$res = $this->_tc->findAndModify($criteria, $data, $fields, $options);
        
        if (empty($res) && !empty($options['upsert'])) {
        	$data = array('crt_ts' => new \MongoDate(time()),
        			      'mod_ts' => new \MongoDate(time()),
        	);
        	
        	$res = $this->_tc->findAndModify($criteria, array('$set' => $data));
        	
        	$res['inserted'] = true;
        } else {
        	$res['inserted'] = false;
        }
        	
        return $res;
    }

    /**
     * 返回指定查询条件的记录条数
     * 
     * @param array $criteria 查询条件
     * @return integer $count 条数
     * 
     * @author  安佰胜
     */
    public function count($criteria = array()) {
    	// 连接数据库主节点
    	$this->_conn_db(false);
    	
        return $this->_tc->count($criteria);
    }
    
    /**
     * 更新数据
     * 
     * @param  array $criteria 查找条件
     * @param  array $data 更新数据内容
     * @param  array $options 选项
     * @return boolean 是否更新成功
     * 
     * @author 安佰胜
     */
    public function update($criteria, $data, $options = array('w' => true, 'multiple' => true)) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
    	if (key_exists('$set', $data) || key_exists('$inc', $data) || key_exists('$addToSet', $data) ||
            key_exists('$push', $data) || key_exists('$pull', $data) || key_exists('$pullAll', $data)) {
            
            $data['$set']['mod_ts'] = new \MongoDate(time());
            
            $retval = $this->_tc->update($criteria, $data, $options);
        } else {
            $data['mod_ts'] = new \MongoDate(time());
            $retval = $this->_tc->update($criteria, array('$set' => $data), $options);
        }
        
        return $retval['updatedExisting'] ? true : false;
    }
    
    /**
     * 字段值增长更新
     * 
     * @param array $criteria 查询条件
     * @param string $field 增长字段
     * @param integer $val 增长值
     * @param array $extra 附带更新内容
     * @param array $options 选项
     * @return boolean 更新是否成功
     * 
     * @author 安佰胜
     */
    public function increase($criteria, $field, $var = 1, $extra = array(), $options = array()) {
        $data = array('$inc' => array($field => $var));
        
        if (!empty($extra)) {
            $data['$set'] = $extra;
        }
        
        // 连接数据库主节点
        $this->_conn_db(true);
        
        return $this->update($criteria, $data, $options);
    }
    
    /**
     * 字段值减少更新
     * 
     * @param array $criteria 查询条件
     * @param string $field 增长字段
     * @param integer $val 减少值
     * @param array $extra 附带更新内容
     * @param array $options 选项
     * @return boolean 更新是否成功
     * 
     * @author anbaisheng
     */
    public function decrease($criteria, $field, $val = 1, $extra = array(), $options = array()) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
    	return $this->increase($criteria, $field, -$val, $extra, $options);
    }
    
    /**
     * 向数组字段中压入元素
     * 
     * @param array $criteria 查询条件
     * @param string $field 操作字段
     * @param mixed $value 压入的元素
     * @param boolean $unique 是否确保数组内元素唯一
     * @return boolean 是否压入成功
     * 
     * @author anbaisheng 
     */
    public function push($criteria, $field, $value, $unique) {
        // 是否需要保证插入的数据项唯一
        $op = $unique ? '$addToSet' : '$push';
        
        // 检查是否为批零压入（小批量）
        $batch = (is_array($value) && array_keys($value) === range(0, count($value)-1)) ? true : false;
        
        if ($batch) {
            $data = array($op => array($field => array('$each' => $value)));
        } else {
            $data = array($op => array($field => $value));
        }
        
        // 连接数据库主节点
        $this->_conn_db(true);
        
        return $this->update($criteria, $data);
    }
    
    /**
     * 从数组字段中弹出元素
     * 
     * @param array $criteria 查询条件
     * @param string $field
     * @param mixed $value
     * @return boolean 是否弹出成功
     */
    public function pull($criteria, $field, $value) {
        // 检查是否为批量移除
        $batch = (is_array($value) && array_keys($value) === range(0, count($value)-1)) ? true : false;
        
        $op = $batch ? '$pullAll' : '$pull';
        
        $data = array($op => array($field => $value));
        
        // 连接数据库主节点
        $this->_conn_db(true);
        
        return $this->update($criteria, $data);
    }
    
    /**
     * 记录是否存在
     * 
     * @param array $criteria
     */
    public function isExisted($criteria) {
    	$res = $this->findOne($criteria, array('_id' => true));
    	
    	return $res ? true : false;
    }
    
    /**
     * 创建文档引用
     * 
     * @param mixed $cited 被引用的文档或文档的MongoId
     * @return array 引用数组 形如：['$ref' => 集合名称, '$id' => 文档MongoId]
     * 
     * @author 安佰胜
     */
    public function createDBRef($cited) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        return $this->_tc->createDBRef($cited);
    }
    
    /**
     * 获取引用的文档内容
     * 
     * @param array $ref 文档引用
     * @return array 被引用文档的内容
     * 
     * @author 安佰胜
     */
    public function getDBRef($ref) {
    	// 连接数据库主节点
    	$this->_conn_db(false);
    	
        return $this->_tc->getDBRef($ref);
    }
    
    /**
     * 删除数据记录
     * 
     * @param  array $criteria 查找条件
     * @param  boolean $erase 是否从数据库真正擦除
     * @param  array $options 选项
     * @return boolean 删除是否成功
     * 
     * @author 安佰胜
     */
    public function delete($criteria, $erase = false, $options = array()) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        if ($erase) {
            return $this->_tc->remove($criteria, $options);
        } else {
            $data = array('enable' => false);
            
            $options = !empty($options) ? $options : $options = array('w' => true, 'multiple' => true);
            
            return $this->update($criteria, $data, $options);
        }
    }
    
    /**
     * 清空集合（表）中所有记录
     * 
     * @return $boolean 清空是否成功
     * 
     * @author 安佰胜
     */
    public function drop() {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        return $this->_tc->drop();
    }
    
    /**
     * 聚合操作
     * 
     * @param array $pipeline 管道操作列表
     * @param array $options 选项
     * 
     * @author 安佰胜
     */
    public function aggregate($pipeline, $options = array()) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        return $this->_tc->aggregate($pipeline, $options);
    }
    
    /**
     * 分组操作
     * 
     * @param  mixed  $keys    分组的字段
     * @param  array  $initial 初始的数据
     * @param  string $reduce  处理函数(js语法)
     * @param  array  $options 选项
     * @return array
     *
     * @author 王文韬
     */
    public function group($keys, $initial, $reduce, $options = array()) {
        // 连接数据库主节点
        $this->_conn_db(true);

        return $this->_tc->group($keys, $initial, $reduce, $options);
    }

    /**
     * 返回集合（表）信息
     * 
     * @return array 集合（表）信息
     * 
     * @author 安佰胜
     */
    public function getTcInfo() {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        $ret = array();
        
        $ret['tc_name']    = $this->_tc->getName();
        $ret['rec_count']  = $this->_tc->count();
        $ret['index_info'] = $this->_tc->getIndexInfo();
        $ret['slave_ok']   = $this->_tc->getSlaveOk();
        
        return $ret;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\Model::getTcName()
     */
    public function getTcName() {
    	return $this->_tc->getName();
    }
    
    /**
     * 创建索引
     * 
     * @param array $keys 需要创建索引的字段列表
     * @param array $options 选项
     * @return boolean 是否创建成功
     * 
     * @author 安佰胜
     */
    public function createIndex($keys, $options) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        if (method_exists($this->_tc, 'createIndex')) {
            return $this->_tc->createIndex($keys, $options);
        } else {
            return $this->_tc->ensureIndex($keys, $options);
        }
    }
    
    /**
     * 删除索引
     * 
     * @param array $keys 需要删除索引的字段列表
     * @return boolean 是否删除成功
     * 
     * @author 安佰胜
     */
    public function deleteIndex($keys) {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        return $this->_tc->deleteIndex($keys);
    }
    
    /**
     * 删除所有索引
     * 
     * @return boolean 是否删除成功
     * 
     * @author 安佰胜
     */
    public function deleteIndexes() {
    	// 连接数据库主节点
    	$this->_conn_db(true);
    	
        return $this->_tc->deleteIndexes();
    }
    
    /**
     * 获取DataTable数据内容
     *
     * @param array $cond
     * @param array $fields 主表需要获取的字段
     * @param array $displays
     * $displays = array('id' => array('type' => 'checkbox'),
     *                   'name' => array('type' => 'query', 'coll' => 'users', 'key' => '_id', 'field' => 'name'),
     *                   'frd_cnt' => array('type' => 'count', 'coll' => 'friends', 'p_key' => '_id', 'f_key' => 'u_id', 'link' => 'friend.list'),
     *                   'phone' => true,
     *                   'crt_ts' => array('type' => 'date', 'fmt' => 'Y-M-D'),
     *                   'gender' => array('type' => 'enum', 'refills' => array('1' => '男', '2' => '女')),
     *                   'op' => array('type' => 'link', 
     *                                 'acts' => array('edit' => '编辑', 'view' => '查看'), 
     *                                 'js' => array('edit' => 'modify'),
     *                                 'on' => array(
     *                                             //该示例意义为:'status'字段的值等于1时显示,语法格式同mongodb语法格式
     *                                             'edit' => array('status' => 1),
     *                                 
     *                                              //该示例意义为:'name'字段的值不等于某值时显示,语法格式同mongodb语法格式
     *                                              'view' => array('name' => array('$ne' => 'xxx')),
     *                                          ),
     *                                 ),
     *                                 
     *                                 'style' => 'normal', // normal-普通模式 dropdown-下拉按钮
     *                   ),
     *                   ),
     *
     * @author 安佰胜
     */
    public function getDataTable($cond, $fields, $displays) {
    	$draw = $cond['draw'];
    	$filters = $cond['filters']; // 目前仅支持“与”条件过滤（精确匹配），格式：city=北京市&dst=海淀区
    	$srch_cols = $cond['srch_cols']; // 多个字段名以英文逗号间隔，如：username,desc
    	$srch_word = $cond['srch_word'];
    	$sort_col = $cond['sort_col'];
    	$sort_dir = $cond['sort_dir'];
    	$start  = $cond['start'];
    	$length = $cond['length'];
    	$criteria = array('enable' => true);
    
    	// 计算总的记录条数
    	$total = $this->count($criteria);
    
    	// 准备过滤器
    	if (!empty($filters)) {
    		$pieces = explode('&', $cond['filters']);
    			
    		foreach ($pieces as $p) {
    			list($f, $v) = explode('=', $p);
    			$v = trim($v);
                // 处理区间查询
                if (preg_match("/^\[.*\]$/", $v)) {
                    $range = explode(',', substr($v, 1, -1));
                    $start_value = $range[0];
                    $end_value = $range[1];
                    // 左边界
                    if (!empty($start_value)) {
                        if (preg_match('/_ts$/', $f)) {
                            $start_value = new \MongoDate(strtotime($start_value));
                        } else {
                            $start_value = (int)$start_value;
                        }
                        $criteria[$f]['$gte'] = $start_value;
                    }
                    // 右边界
                    if (!empty($end_value)) {
                        if (preg_match('/_ts$/', $f)) {
                            $end_value = new \MongoDate(strtotime($end_value) + 24*60*60);
                        } else {
                            $end_value = (int)$end_value;
                        }
                        $criteria[$f]['$lte'] = $end_value;
                    }

                } else if(substr($v,0,2) === '{{' && substr($v,-2) === '}}'){
                    $mongo = substr($v,2,-2);
                    $criteria[$f] = new \MongoId($mongo);
                } else if(substr($v,0,1) === '|' && substr($v,-1) === '|') { // 适用于字符串型的数字
                    $str = substr($v,1,-1);
                    $criteria[$f] = $str;
                } else if (!empty($f) && $v !== 'all' && $v !== '') {
                	if($v == 'true'){
                		$c_val = true;
                	}else if($v == 'false'){
                		$c_val = false;
                	}else if(is_numeric($v)){
                		$c_val = intval($v);
                	}else{
                		$c_val = $v;
                	}
                	
    				$criteria[$f] = $c_val;
    			}
    		}
    	}
		
    	// 准备模糊搜索条件
    	if (!empty($srch_cols) && !empty($srch_word)) {
    		$cols = explode(',', $srch_cols);
    			
    		$criteria['$or'] = array();
    		foreach ($cols as $p) {
    			$search = preg_quote($srch_word, '/');
    			$criteria['$or'][][$p] = new \MongoRegex('/'.$search.'/i');
    		}
    	}

    	$temp = array();
    	foreach ($fields as $f) {
    		$temp[$f] = true;
    	}
    	$fields = $temp;
    
    	// 执行查询
    	$res = $this->find($criteria, $fields, array("$sort_col" => $sort_dir), $start, $length);
    	$count = $this->count($criteria);
    
    	// 整理返回的记录
    	$data = array();
    	foreach ($res as $p) {
    		// $p 为行记录
    		$temp = array();
    			
    		foreach ($displays as $k => $v) {
    			// $item 为某一列值
    			$item = isset($p[$k]) ? $p[$k] : null;
    
    			// 处理多级数组问题
    			if (strstr($k, '.')) {
    				$bricks = explode('.', $k);
    				$t = $p;
    				foreach ($bricks as $b) {
    					$item = $t[$b];
    					$t = $item;
    				}
    			}
    
    			if (!is_array($v)) {
    				$temp[] = $item;
    			} else {
    				switch ($v['type']) {
    					case 'checkbox':
    						$id = $p['_id']->{'$id'};
    						$temp[] = "<input type='checkbox' id='{$id}' class='checklist'>";
    						break;
    					case 'date':
    						$temp[] = date($v['fmt'], $item->{'sec'});
    						break;
    					case 'enum':
    						$val = $item;
    						$temp[] = $v['refills'][$val];
    						break;
    					case 'query':
    						$crumbs = explode('_', $v['coll']);
    						foreach ($crumbs as &$c) {$c = ucfirst($c);}
    						$model_class = implode('', $crumbs);
    						$func = 'getM'. $model_class;
    						$model_inst = june_get_apps_db_conn_pool()->$func();
    						
    						// 支持返回多个字段
    						if (strstr($v['field'], ',')) {
    							$cols = explode(',', $v['field']);
    							$fields = array();
    							foreach ($cols as $col) {
    								$fields[$col] = true;
    							}
    						} else {
    							$cols = array($v['field']);
    							$fields = array($v['field'] => true);
    						}
    							
    						$query_res = $model_inst->findOne(array($v['key'] => $item), $fields);
    						
    						foreach ($cols as $col) {
    							$temp[] = $query_res[$col];
    						}
    						break;
    					case 'count':
    						$crumbs = explode('_', $v['coll']);
    						foreach ($crumbs as &$c) {$c = ucfirst($c);}
    						$model_class = implode('', $crumbs);
    						$func = 'getM'. $model_class;
    						$model_inst = june_get_apps_db_conn_pool()->$func();
    						
    						$p_key = $v['p_key'];
    						$p_val = $p[$p_key];
    						
    						$cnt_html = "";
    						if (!empty($v['link'])) {
    							if ($p_val instanceof \MongoId) {
    								$p_val = $p->{'$id'};
    							}
    							$cnt_html .= '<a href="' . $v['link'] . '&' . $p_key . '=' . $p_val . '" target="_blank" >';
    						}
    						
    						$cnt_html .= $model_inst->count(array($v['f_key'] => $p[$p_key], 'enable' => true));
    						
    						if (!empty($v['link'])) {
    							$cnt_html .= '</a>';
    						}
    						
    						$temp[] = $cnt_html;
    						break;
    					case 'link':
    						$id = $p['_id']->{'$id'};
    							
    						// 创建链接
    						if (isset($v['style']) && $v['style'] == 'dropdown') {
    							$op_html = '<div class="btn-group">' .
    							           '<a href="javascript:;" data-toggle="dropdown" class="btn btn-primary dropdown-toggle f-s-12" aria-expanded="false">操作 <span class="caret"></span></a>' .
    							           '<ul class="dropdown-menu" style="min-width:60px;">';
    						} else {
    							$op_html = "";
    						}
    						
    						$acts = $v['acts'];
    						$js = isset($v['js']) ? $v['js'] : null;
    						$on = isset($v['on']) ? $v['on'] : null;
    						foreach ($acts as $act => $nm) {
    							// 处理js函数
    							$js_html = "";
    							if (!empty($js[$act])) {
    								$js_html = "onclick=\"{$js[$act]}('{$id}')\"";
    							}
    							
    							// 处理显示条件
    							$is_on = true;
    							if (isset($on[$act]) && is_array($on[$act])) {
									
    								foreach ($on[$act] as $c_k => $c_v) {
                                        if(is_array($c_v)) {
                                            foreach($c_v as $c_kk => $c_vv) {
                                                switch ($c_kk) {
                                                    case '$ne':
                                                        if ($p[$c_k] === $c_vv) $is_on = false;
                                                        break;
                                                    case '$gte':
                                                        if ($p[$c_k] < $c_vv) $is_on = false;
                                                        break;
                                                    case '$gt':
                                                        if ($p[$c_k] <= $c_vv) $is_on = false;
                                                        break;
                                                    case '$lte':
                                                        if ($p[$c_k] > $c_vv) $is_on = false;
                                                        break;
                                                    case '$lt':
                                                        if ($p[$c_k] >= $c_vv) $is_on = false;
                                                        break;
                                                    default:
                                                        if ($p[$c_k] !== $c_vv) $is_on = false;
                                                        break;
                                                }
                                            }

                                        }else{
                                            if ($p[$c_k] !== $c_v) $is_on = false;
                                        }
    									if ($is_on == false) break;
    								}
    							} elseif (!isset($on[$act])) {
    								$is_on = true;
    							} elseif (!is_array($on[$act])) {
    								if ($p[$act] !== $on[$act]) {
    									$is_on = false;
    									break;
    								}
    							}
    							
    							// 处理锚点参数
    							$fragment = '';
    							$toggle = '';
    							if (strstr($act, '#')) {
    								$crumbs = explode('#', $act);
    								
    								if ($crumbs > 1) {
    									$act = $crumbs[0];
    									$fragment = $crumbs[1];
    									$toggle = "data-toggle='modal'";
    								}
    							}
    							
    							// 处理操作列表样式
    							if ($is_on) {
    								if (isset($v['style']) && $v['style'] == 'dropdown') {
    									$op_html .= '<li>';
    								}
    								
    								if (empty($act)) {
    									$op_html .= "<a class='op_{$fragment}' data-id='{$id}' {$js_html} href='#{$fragment}' {$toggle} >{$nm}</a> ";
    								} else {
    									$action = june_gen_full_action($act);
    									$class = str_replace('.', '_', $action);
                                        if (empty($js_html)) {
    									   $op_html .= "<a class='op_{$class}' data-id='{$id}' {$js_html} href='index.php?action={$action}&id={$id}#{$fragment}' {$toggle} >{$nm}</a> ";
                                        } else {
                                           $op_html .= "<a class='op_{$class}' data-id='{$id}' {$js_html} href='#{$fragment}' {$toggle} >{$nm}</a> ";
                                        }
    								}
    								
    								if (isset($v['style']) && $v['style'] == 'dropdown') {
    									$op_html .= '</li>';
    								}
    							}
    							
    						}
    						
    						if (isset($v['style']) && $v['style'] == 'dropdown') {
    							$op_html .= '</ul></div>';
    						}
    							
    						$temp[] = $op_html;
    						break;
    					default:
    						$temp[] = $item;
    				}
    			}
    		}
    		array_push($data, $temp);
    	}
    
    	$res = array(
    			'draw'            => $draw,
    			'recordsTotal'    => $total,
    			'recordsFiltered' => $count,
    			'data'            => $data
    	);
    
    	return $res;
    }
    
}
?>