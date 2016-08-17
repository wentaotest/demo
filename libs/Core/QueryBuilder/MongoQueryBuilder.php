<?php namespace June\Core\QueryBuilder;

use June\Core\QueryBuilder;

final class MongoQueryBuilder extends QueryBuilder {
    // 改造后使用的查询操作符列表
    protected $operators = array(
        'Basic'   => array('=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'regex', 'not regex'),
        'Between' => array('between', 'not between'),
        'In'      => array('in', 'not in', 'all'),
        'Exists'  => array('exists'),
        'Geo'     => array('geowithin', 'geointersects', 'near', 'nearsphere', 'geometry')
    );
    
    // 操作符转化列表
    protected $conversion = array(
        '='  => '=',
        '!=' => '$ne',
        '<>' => '$ne',
        '<'  => '$lt',
        '<=' => '$lte',
        '>'  => '$gt',
        '>=' => '$gte',
    );
    
    public function __construct($tc_name) {
        parent::__construct($tc_name);
    }
    
    /**
     * 将符合条件的字符串转化为MongoId
     * 
     * @param string $id 待转化的字符串
     * @return MongoId $id 转化完的MongoId
     */
    public function convertToMongoId($id) {
        // 如果$id是24位纯16进制字符串，怎转换为MongoId类型
        if (is_string($id) && strlen($id) == 24 && ctype_xdigit($id)) {
            return new MongoId($id);
        }

        return $id;
    }
    
    public function fields(array $cols = array()) {
    	
    }
    
    /**
     * 构建where“与”条件
     * 
     * @param string $field 操作字段
     * @param string $operator 查询操作符
     * @param mixed $value 操作值
     * @param string $boolean 逻辑运算符号
     * @param Query 查询器对象
     */
    public function where($field, $operator = null, $value = null, $boolean = 'and') {
        // 去掉操作符中可能存在的 '$' 符号    
        if (func_num_args() == 3) {
            if (starts_with($operator, '$')) {
                $operator = substr($operator, 1);
            }
        }
        
        // 兼容只有两个参数的情况，默认将第二个参数认定为$value，操作符认定为 '='，注意：不推荐这样使用！！！
        if (func_num_args() == 2) {
            list($value, $operator) = array($operator, '=');
        }
        
        // 子查询功能
        if ($value instanceof Closure) {
            // TODO 待实现
        }
        
        // 设定where语句类型
        foreach ($this->operators as $key => $v) {
            if (in_array($operator, $v)) {
                $type = $key;break;
            }
        }
        
        $val_str = is_array($value) ? 'values' : 'value';
        $$val_str = $value;

        $this->_wheres[] = compact('type', 'field', 'operator', "$val_str", 'boolean');
        
        return $this;
    }
    
    /**
     * 构建orWhere“或”条件
     * 
     * @param string $field 操作字段
     * @param string $operator 查询操作符
     * @param mixed $value 操作值
     * @param Query 查询器对象
     */
    public function orWhere($field, $operator = null, $value = null) {
        return $this->where($field, $operator, $value, 'or');
    }
    
    /**
     * 编译where语句
     * 
     * @return array 编译的中间结果
     */
    public function compileWheres() {
        $this->_wheres = $this->_wheres ?: array();
        $this->_compiled = array();
        
        foreach ($this->_wheres as $key => &$where) {
            
            // 将操作符转化为小写
            $where['operator'] = strtolower($where['operator']);
            
            // 如果操作列中含有'id'字样，则将对应的符合条件的数值转换为MongoId
            if (isset($where['field']) && ends_with($where['field'], 'id', false)) {
                if (isset($where['value'])) {
                    $where['value'] = $this->convertToMongoId($where['value']);
                }
                if (isset($where['values']) && is_array($where['values'])) {
                    foreach ($where['values'] as &$value) {
                        $value = $this->convertToMongoId($value);
                    }
                }
            }

            if ($key == 0 and count($this->_wheres) > 1 and $where['boolean'] == 'and'){
                $where['boolean'] = $this->_wheres[$key + 1]['boolean'];
            }
            
            // 编译where语句
            $method = "_compileWhere{$where['type']}";
            $result = $this->{$method}($where);
            
            // 处理 and 和 or 关系
            if ($where['boolean'] ==  'or') {
                $result = array('$or' => array($result));
            } elseif (count($this->_wheres) > 1) {
                $result = array('$and' => array($result));
            }

            $this->_compiled = array_merge_recursive($this->_compiled, $result);
        }
        
        return $this;
    }
    
    /**
     * 编译basic语句
     * 
     * @return array 编译的中间结果
     */
    protected function _compileWhereBasic($where) {
        extract($where);
        
        // 将like语句转化为MongoRegex，注：%world%表示查找包含world的字符串，world%表示查找包含以world结尾的字符串
        if (in_array($operator, array('like', 'not like'))) {
            $operator = starts_with('not', $operator) ? 'not' : '=';

            $regex = str_replace('%', '', $value);

            if (!starts_with('%', $value)) {
                $regex = '^' . $regex;
            }

            if (!ends_with('%', $value)) {
                $regex = $regex . '$';
            }

            $value = new MongoRegex("/$regex/i");
        }

        // 将普通正则表达式转化为MongoRegex
        if (in_array($operator, array('regex', 'not regex'))) {
            $operator = starts_with('not', $operator) ? 'not' : '=';

            if (!$value instanceof MongoRegex) {
                $value = new MongoRegex($value);
            }
        }

        if (!isset($operator) || $operator == '=') {
            $query = array($field => $value);
        } elseif (array_key_exists($operator, $this->conversion)) {
            $query = array($field => array($this->conversion[$operator] => $value));
        } else {
            $query = array($field => array('$'.$operator => $value));
        }

        return $query;
    }
    
    /**
     * 编译between语句
     * 
     * @return array 编译的中间结果
     */
    protected function _compileWhereBetween($where) {
        extract($where);

        if (starts_with($operator, 'not')) {
            return array(
                '$or' => array(
                    array($field => array('$lte' => $values[0])),
                    array($field => array('$gte' => $values[1]))
                ));
        } else {
            return array(
                $field => array(
                    '$gte' => $values[0],
                    '$lte' => $values[1]
                ));
        }
    }
    
    /**
     * 编译between语句
     * 
     * @return array 编译的中间结果
     */
    protected function _compileWhereIn($where) {
        extract($where);

        if ($not) {
            return array($field => array('$nin' => array_values($values)));
        } else {
            return array($field => array('$in' => array_values($values)));
        }
    }
    
    /**
     * 编译exists语句
     * 
     * @return array 编译的中间结果
     */
    protected function _compileWhereExists($where) {
        extract($where);

        if ($not) {
            return array($field => array('$exists' => false));
        } else {
            return array($field => array('$exists' => true));
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::getCompiled()
     * 
     * @author 安佰胜
     */
    public function getCompiled() {
    	
    }
    
    /**
     * 返回查询结果
     * 
     * @return array
     */
    public function get() {
        $this->compileWheres();

        print_r('<pre>');
        print_r($this->_compiled);
        print_r('</pre>');

        $cursor = $this->_tc->find($this->_compiled);
        
        $ret = array();
        foreach ($cursor as $p) {
            $ret[] = $p;
        }
        
        return $ret;
    }

    /**
     * 清空where条件
     */
    public function refresh() {
        $this->_wheres   = null;
        $this->_compiled = null;
    }
}
?>