<?php namespace June\Core;

/**
 * 数据库集合或表的Model抽象类
 *
 * @author 安佰胜
 */
abstract class Model {
	protected $_db_conn_inst; // 数据库连接类实例
	protected $_db_serv; // 数据库服务器连接
    protected $_db; // 数据库连接
    protected $_tc; // 表（table）或集合（collection）
    
    public $query; // 查询器
    
    /**
     * 构造函数
     * 
     * @param DbConn $db_conn
     * @param string $tc_name
     * 
     * @author 安佰胜
     */
    abstract function __construct($db_conn, $tc_name);

    /**
     * 插入数据
     * 
     * @param  array $values 插入的数据
     * @return boolean 插入是否成功
     */
    abstract function insert($values);
    
    /**
     * 查找数据记录列表
     * 
     * @param  array   $criteria 查找条件
     * @param  array   $fields 返回字段
     * @param  array   $sort_by 排序方式
     * @param  integer $skip 跳过记录的数量
     * @param  integer $limit 限制返回记录的数量
     * @return mixed 查找结果
     */
    abstract function find($criteria, $fields = array(), $sort_by = array(), $skip = NULL, $limit = NULL);
    
    /**
     * 查找单条数据记录
     * 
     * @param array $criteria 查找条件
     * @param array $fields 返回字段
     * @return mixed 查找结果
     */
    abstract function findOne($criteria, $fields = array());
    
    /**
     * 查找并修改某条记录（原子操作）
     * 
     * @param  array $criteria 查找条件
     * @param  array $data 更新数据内容
     * @param  array $fields 返回字段
     * @param  array $options 选项
     * @return mixed 查找结果
     */
    abstract function findAndModify($criteria, $data = array(), $fields = array(), $options = array());
    
    /**
     * 更新数据
     * 
     * @param  array $criteria 查找条件
     * @param  array $data 更新数据内容
     * @param  array $options 选项
     * @return boolean 是否更新成功
     */
    abstract function update($criteria, $data, $options = array());
    
    /**
     * 删除数据记录
     * 
     * @param  array $criteria 查找条件
     * @param  boolean $erase 是否从数据库真正擦除
     * @param  array $options 选项
     * @return boolean 删除是否成功
     */
    abstract function delete($criteria, $erase = false, $options = array());
    
    /**
     * 清空集合（表）中所有记录
     * 
     * @return $boolean 清空是否成功
     */
    abstract function drop();
    
    /**
     * 字段值增长更新
     * 
     * @param array $criteria 查询条件
     * @param string $field 增长字段
     * @param integer $val 增长值
     * @param array $extra 附带更新内容
     * @param array $options 选项
     * @return boolean 更新是否成功
     */
    abstract function increase($criteria, $field, $var = 1, $extra = array(), $options = array());
    
    /**
     * 字段值增长更新
     * 
     * @param array $criteria 查询条件
     * @param string $field 增长字段
     * @param integer $val 增长值
     * @param array $extra 附带更新内容
     * @param array $options 选项
     * @return boolean 更新是否成功
     */
    abstract function decrease($criteria, $field, $val = -1, $extra = array(), $options = array());
    
    /**
     * 返回集合（表）信息
     * 
     * @return array 集合（表）信息
     */
    abstract function getTcInfo();
    
    /**
     * 返回集合（表）名称
     * 
     * @author 安佰胜
     */
    abstract function getTcName();
    
    /**
     * 创建索引
     * 
     * @param array $keys 需要创建索引的字段列表
     * @param array $options 选项
     * @return boolean 是否创建成功
     */
    abstract function createIndex($keys, $options);
    
    /**
     * 删除索引
     * 
     * @param array $keys 需要删除索引的字段列表
     * @return boolean 是否删除成功
     */
    abstract function deleteIndex($keys);
    
    /**
     * 删除所有索引
     * 
     * @return boolean 是否删除成功
     */
    abstract function deleteIndexes();

}
?>