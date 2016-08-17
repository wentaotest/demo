<?php namespace June\Core;

/**
 * 数据库查询语句的构造器
 *
 * @author 安佰胜
 */
abstract class QueryBuilder {
    protected $_tc;
    
    protected $_fields;
    protected $_wheres;
    protected $_sort;
    protected $_skip;
    protected $_limit;
    
    
    /**
     * 编译的最终结果
     * 
     * @var string|array
     */
    protected $_compiled;
    
    public function __construct($tc_name) {
        $this->_tc = $tc_name;
    }
    
    /**
     * 需要返回的字段
     * 
     * @param array $cols 关联数组时，键名是列名，键值是别名；索引数组时，键值为列名
     */
    abstract function fields(array $cols = array());
    
    /**
     * 构建where“与”条件
     * 
     * @param string $field 操作字段
     * @param string $operator 查询操作符
     * @param mixed $value 操作值
     * @param string $boolean 逻辑运算符号
     * @param Query 查询器对象
     */
    abstract function where($field, $operator = null, $value = null, $boolean = 'and');
    
    /**
     * 构建orWhere“或”条件
     * 
     * @param string $field 操作字段
     * @param string $operator 查询操作符
     * @param mixed $value 操作值
     * @param Query 查询器对象
     */
    abstract function orWhere($field, $operator = null, $value = null);

    /**
     * 编译查询语句
     */
    abstract function compile();

    /**
     * 清空where条件
     */
    abstract function refresh();
    
    /**
     * 编译where语句
     * 
     * @return array 编译的中间结果
     */
    abstract function compileWheres();
    
    /**
     * 编译basic语句
     * 
     * @param array $where
     */
    abstract protected function _compileWhereBasic($where);
    
    /**
     * 编译between语句
     * 
     * @param array $where
     */
    abstract protected function _compileWhereBetween($where);
    
    /**
     * 编译in语句
     * 
     * @param array $where
     */
    abstract protected function _compileWhereIn($where);
    
    /**
     * 编译exists语句
     * 
     * @param array $where
     */
    abstract protected function _compileWhereExists($where);

    abstract function skip($num);

    abstract function limit($num);

    abstract function sort(array $sort_by);

    abstract function count();
    
}
?>