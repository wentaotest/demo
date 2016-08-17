<?php namespace June\Core\QueryBuilder;

use June\Core\QueryBuilder;
use June\Core\JuneException;

final class MysqlQueryBuilder extends QueryBuilder {
	
	protected $_compiled_wheres;
	
	protected $_compiled_from;
	
	protected $_compiled_limit;
	
	protected $_compiled_sort;
	
	protected $_query_type = 'SELECT';
	
    // 改造后使用的查询操作符列表
    protected $operators = array(
        'Basic'   => array('=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'regex', 'not regex'),
        'Between' => array('between', 'not between'),
        'In'      => array('in', 'not in', 'all'),
        'Exists'  => array('exists'),
    );
    
    public function __construct($tc_name) {
    	parent::__construct($tc_name);
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::fields()
     * @return MysqlQueryBuilder
     * 
     * @author 安佰胜
     */
    public function fields(array $cols = array()) {
    	if (!empty($cols)) {
    		foreach ($cols as $key => $val) {
    			$c_str = '';
    			
    			$c_str = is_bool($val) ? " `$key`," : " `$val`,";
    			
    			$this->_fields .= $c_str;
    		}
    		
    		$this->_fields = trim($this->_fields, ',');
    		$this->_fields .= ' ';
    	} else {
    		$this->_fields = ' * ';
    	}

    	return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::where()
     * @return MysqlQueryBuilder
     * 
     * @author 安佰胜
     */
    public function where($field, $operator = null, $value = null, $boolean = 'and') {
    	// 兼容只有两个参数的情况，默认将第二个参数认定为$value，操作符认定为 '='，注意：不推荐这样使用！！！
    	if (func_num_args() == 2) {
    		list($value, $operator) = array($operator, '=');
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
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::orWhere()
     * @return MysqlQueryBuilder
     * 
     * @author 安佰胜
     */
    public function orWhere($field, $operator = null, $value = null) {
    	return $this->where($field, $operator, $value, 'or');
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::compileWheres()
     * @return MysqlQueryBuilder
     * 
     * @author 安佰胜
     */
    public function compileWheres() {
    	if (!empty($this->_wheres) && is_array($this->_wheres) ) {
    		
    		$compiled_str = "";
    		
    		foreach ($this->_wheres as $key => &$where) {
	    		// 将操作符转化为小写
	    		$where['operator'] = strtolower($where['operator']);
	    		
	    		// 编译where语句
	    		$method = "_compileWhere{$where['type']}";
	    		$result = $this->{$method}($where);
	    		 
	    		// 处理 and 和 or 关系
	    		if (count($this->_wheres) < 2 && empty($this->_compiled_wheres)) {
	    			$result = ' 1 AND ' . $result;
	    		} else {
	    			if ($where['boolean'] ==  'or') {
	    				$result = empty($compiled_str) ? $result : ' OR ' . $result;
	    			} else {
	    				$result = empty($compiled_str) ? $result : ' AND ' . $result;
	    			}
	    		}
	    		
	    		$compiled_str .= $result;
    		}
    		
    		unset($this->_wheres);
    		
    		$this->_compiled_wheres = empty($this->_compiled_wheres) ? $compiled_str : "($this->_compiled_wheres) AND $compiled_str";
    	}
    	
    	return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::_compileWhereBasic()
     * @return string
     * 
     * @author 安佰胜
     */
    protected function _compileWhereBasic($where) {
    	extract($where);
    	
    	$query = "";
    	switch ($operator) {
    		case '>':
    		case '<':
    		case '>=':
    		case '<=':
    		case '!=':
    		case '=':
    			if (is_int($value) || is_float($value) || is_bool($value)) {
    				$query = "`$field` $operator $value";
    			} else {
    				$query = "`$field` $operator `$value`";
    			}
    			break;
    			
    		case 'like':
    		case 'not like':
    			$query = "`$field` $operator `$value`";
    			break;
    			
    		default:
    			;
    		break;
    	}
    	
    	return $query;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::_compileWhereBetween()
     * @return string
     * 
     * @author 安佰胜
     */
    protected function _compileWhereBetween($where) {
    	
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::_compileWhereIn()
     * @return string
     * 
     * @author 安佰胜
     */
    protected function _compileWhereIn($where) {
    	
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::_compileWhereExists()
     * @return string
     * 
     * @author 安佰胜
     */
    protected function _compileWhereExists($where) {
    	
    }
    
    /**
     * 查询的表名
     * 
     * @author Vincent An
     */
    public function from($table) {
    	$this->_compiled_from = " FROM `$table`";
    	
    	return $this;
    }
    
    /**
     * 连表查询
     * 
     * @param string $join_type           连接类型
     * @param string|array $another_table 被连接的数据表名
     * @param string $on                  连接条件
     * @throws \JuneException
     * @return MysqlQueryBuilder
     * 
     * @author 安佰胜
     */
    public function join($join_type, $another_table, $on) {
    	
    	if (empty($join_type) || empty($another_table) || empty($on)) {
    		$message = "Joined table's 'join_type' or 'another_table' or 'on' should not be empty!";
    		throw new \JuneException($message);
    	}
    	
    	$join_type = strtoupper($join_type);
    	
    	if (!in_array($join_type, array('JOIN', 'LEFT JOIN', 'RIGHT JOIN'))) {
    		$message = "Only 'JOIN' and 'LEFT JOIN' and 'RIGHT JOIN' be supported!";
    		throw new \JuneException($message);
    	}
    	
    	$table = $this->_tc;
    		
    	$this->_compiled_from = " FROM `$table` $join_type `$another_table` ON $on";
    	
    	
    	return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::skip()
     * 
     * @author 安佰胜
     */
    public function skip($num) {
    	if (!is_int($num)) {
    		$message = "Skiping value must be an integer!";
    		throw new JuneException($message);
    	} else {
    		$this->_skip = intval($num);
    	}
    	
    	return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::limit()
     * 
     * @author 安佰胜
     */
    public function limit($num) {
    	if (!is_int($num)) {
    		$message = "Skiping value must be an integer!";
    		throw new JuneException($message);
    	} else {
    		$this->_limit = intval($num);
    	}
    	 
    	return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::sort()
     * 
     * @author 安佰胜
     */
    public function sort(array $sort_by) {
    	if (!empty($sort_by)) {
    		$this->_sort = $sort_by;
    		
    		// 直接编译sort语句
    		$sort_stmt = " ORDER BY";
    		
    		foreach ($sort_by as $col => $dir) {
    			$dir = $dir === 1 ? 'ASC' : 'DESC';
    			$sort_stmt .= " `$col` $dir,";
    		}
    		
    		$this->_compiled_sort = trim($sort_stmt, ',');
    	}
    	
    	return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::count()
     * 
     * @author 安佰胜
     */
    public function count() {
    	
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::compile()
     * @return string
     * 
     * @author 安佰胜
     */
    public function compile() {
    	// 编译WHERE条件
    	$this->compileWheres();
    	
    	// 拼接FROM
    	if (empty($this->_compiled_from)) {
    		$this->_compiled_from = " FROM `$this->_tc`";
    	}
    	
    	// 拼接FEILDS
    	if (empty($this->_fields)) {
    		$this->_fields = ' *';
    	}
    	
    	// 拼接基本SQL语句
    	$this->_compiled = $this->_query_type . $this->_fields . $this->_compiled_from . ' WHERE ' . $this->_compiled_wheres;
    	
    	// 拼接SORT
    	if (!empty($this->_compiled_sort)) {
    		$this->_compiled .= $this->_compiled_sort;
    	}
    	
    	// 拼接LIMIT
    	if (!empty($this->_limit)) {
    		// 编译LIMIT语句
    		$skip = empty($this->_skip) ? 0 : $this->_skip;
    		$this->_compiled_limit = " LIMIT $skip,$this->_limit";
    		
    		// 拼接
    		$this->_compiled .= $this->_compiled_limit;
    	}
    	
    	return $this->_compiled;
    }
    
    /**
     * {@inheritDoc}
     * @see \June\Core\QueryBuilder::refresh()
     * 
     * @author 安佰胜
     */
    public function refresh() {
    	
    }
    
    /**
     * 返回编译过的WHERE语句
     * 
     * @author 安佰胜
     */
    public function getCompiledWheres() {
    	return $this->_compiled_wheres;
    }
}