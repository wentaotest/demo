<?php namespace June\Core;

/**
 * 数据库连接抽象类定义
 *
 * @author 安佰胜
 */
abstract class DbConn {
	protected static $_inst = array();
    protected $_models = array();
    
    /**
     * 获取数据库连接类的单例
     * 
     * 注意：单例的子类如果不使用数组各自存储，会造成覆盖或无法得到另一个子类的实例
     * 
     * @param array $opt
     * @return \June\Core\DbConn
     * 
     * @author 安佰胜
     */
	public static function getInstance($opt) {
		
		$class = get_called_class();
		
        if (empty(self::$_inst[$class])) {
            self::$_inst[$class] = new static($opt);
        }
        
        return self::$_inst[$class];
    }
    
    /**
     * 获取Model类的实例
     * 
     * @param string $tc_name
     * 
     * @author 安佰胜
     */
    abstract function getModel($tc_name);

}
?>