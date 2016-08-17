<?php namespace June\Core;

/**
 * 所有组件使用命名空间
 * 所有组件之间的依赖关系均通过依赖注入方式实现！
 * 
 * 依赖注入的方式：构造函数注入、成员属性注入等，本容器约定只使用构造函数注入！！
 * 
 * 本容器支持：autowrite-依赖自动注入
 * 
 * @author 安佰胜
 *
 */
class DIContainer {
	// 保存容器实例本身
	private static $_inst;

	// 保存组件类的别名
	private $_aliases = array();

	// 保存依赖组件的单例
	private $_singletons = array();

	// 保存依赖组件的定义（或者匿名函数，或者对象）
	private $_definitions = array();

	// 保存依赖组件的构造函数参数
	private $_params = array();

	private $_dependencies = array();

	private $_reflections = array();

	private function __construct() {}

	static public function getInstance() {
		if (empty(self::$_inst)) {
            self::$_inst = new DIContainer();
        }
        
        return self::$_inst;
	}

	/**
	 * 向容器注册一个普通组件（组件的类名、属性定义、构造函数参数）
	 * 
	 * 使用举例：注册一个类名为 June\Core\Router 的组件到容器
	 * 方法一：Container::getInstance()->register('June\Core\Router');
	 * 方法二：Container::getInstance()->register('router', 'June\Core\Router');
	 * 方法三：Container::getInstance()->register('router', array('class_name' => 'June\Core\Router'));
	 * 
	 * @param string $component_name 组件别名或组件对应的类名
	 * @param mixed $definition      组件定义信息
	 * @param array $params          组件初始化参数
	 * @return DIContainer
	 * 
	 * @author 安佰胜
	 */
	public function register($component_name, $definition = array(), $params = array()) {
		// 保存组件定义信息
		$this->_setDefinitions($component_name, $definition);

		// 保存组件类构造函数的参数列表
		$this->_params[$component_name] = $params;

		unset($this->_singletons[$component_name]);

		return $this;
	}

	/**
	 * 向容器注册一个单例组件（组件的类名、属性定义、构造函数参数）
	 * 
	 * @param string $component_name 组件别名或组件对应的类名
	 * @param mixed $definition      组件定义信息
	 * @param array $params          组件初始化参数
	 * @return DIContainer
	 * 
	 * @author 安佰胜
	 */
	public function registerSingleton($component_name, $definition = array(), $params = array()) {
		// 保存组件定义信息
		$this->_setDefinitions($component_name, $definition);

		// 保存组件类构造函数的参数列表
		$this->_params[$component_name] = $params;

		$this->_singletons[$component_name] = null; // 注意与register方法中的不同之处

		return $this;
	}

	/**
	 * 向容器注册一个匿名函数或对象
	 * 
	 * 使用举例：
	 * 方法一：Container::getInstance()->register('router', function() { ... });
	 * 方法二：Container::getInstance()->register('router', $object);
	 * 
	 * @param string $component_name 组件别名或组件对应的类名
	 * @param mixed $definition 组件定义信息
	 * @return DIContainer
	 * 
	 * @author 安佰胜
	 */
	public function registerInvokable($component_name, $definition) {
		// 保存组件定义信息
		$this->_setDefinitions($component_name, $definition);

		return $this;
	}

	/**
	 * 保存依赖组件的定义信息（经过改造后的definition有三种类型：array，callable，object）
	 * 
	 * @param string $class 组件类名
	 * @param mixed $definitions 组件定义信息
	 *
	 */
	private function _setDefinitions($component_name, $definition) {
		if (empty($definition) || is_string($definition)) {

			// 举例一：Container::getInstance()->register('June\Core\Router');
			// 举例二：Container::getInstance()->register('router', 'June\Core\Router');

			$class_name = empty($definition) ? $component_name : $definition;
			$standard_definition = array('class_name' => $class_name);
		} elseif (is_array($definition)) {

			// 举例三：Container::getInstance()->register('router', array('class_name' => 'June\Core\Router'));

			$class_name = isset($definition['class_name']) ?: null;
			$standard_definition = array_merge($definition, array('class_name' => $class_name));
		} elseif (is_callable($definition) || is_object($definition)) {

			// 举例五：Container::getInstance()->register('router', function() { ... });
	        // 举例六：Container::getInstance()->register('router', $object);

			$this->_definitions[$component_name] = $definition;

			return $this;
		} else {
			throw new \Exception("Unsupported definition type for DI Component '$component_name'", 1);
		}

		// if (!empty($class_name) && strpos($class_name, '\\') !== false) {
		if (!empty($class_name)) {
			$this->_definitions[$component_name] = $standard_definition;

			if ($class_name !== $component_name) {
				$this->_aliases[$component_name] = $class_name;
			}
		} else {
			throw new \Exception("Class reuqired by DI Component '$component_name' does NOT exist! ", 1);
		}

		return $this;
	}


	/**
	 * 获得组件的依赖关系
	 * 注意：能够自动加载依赖关系，必须遵从以下的前提规则：组件内的依赖通过构造函数注入！！！
	 * 
	 * @param string 组件名
	 * @return array
	 */
	private function _getDependencies($component_name) {
		if (isset($this->_definitions[$component_name]['class_name'])) {
			$class_name = $this->_definitions[$component_name]['class_name'];
		} else {
			$class_name = $component_name;
		}

		// 如果该类的reflection对象已经被缓存，表示其依赖已经被解析过
		if (isset($this->_reflections[$component_name])) {
			return array($this->_reflections[$component_name], $this->_dependencies[$component_name]);
		}

		// 依赖信息数组
		$dependencies = array();

		// 使用反射机制获取组件类构造函数的参数列表，并分析存在的依赖信息
		$reflection = new \ReflectionClass($class_name);

		$constructor = $reflection->getConstructor();
		if (isset($constructor)) {
			foreach ($constructor->getParameters() as $param) {
				if ($param->isDefaultValueAvailable()) {
					// 如果构造函数存在默认值，将默认值作为依赖（默认值肯定是简单类型）
					$dependencies[] = $param->getDefaultValue();
					
				} else {
					// 如果构造函数没有默认值，则为其创建一个引用（Instance类型的引用，它此时起到了占位标记的作用）
					$c = $param->getClass();
					$dependencies[] = Instance::of($c === null ? null : $c->getName());
				}
			}
		}

		// 保存组件的反射类对象
		$this->_reflections[$component_name] = $reflection;

		// 保存组件的依赖信息
		$this->_dependencies[$component_name] = $dependencies;

		return array($reflection, $dependencies);
	}

	/**
	 * 解析组件的所有依赖（主要是将需要实例化的依赖组件实例化，并得到实例的引用）
	 * 
	 * @param array $dependencies 组件的依赖信息数组
	 * @param object $reflection 组件的反射类对象
	 * @return array 解析后的组件依赖信息数组
	 */
	private function _parseDependencies($dependencies, $reflection = null) {
		foreach ($dependencies as $idx => $dependency) {
			if ($dependency instanceof Instance) {
				if ($dependency->id !== null) {
					// 向容器请求所依赖的实例，递归调用$this->get()
					$dependencies[$idx] = $this->get($dependency->id);
				} elseif ($reflection !== null) {
					// 请求依赖的构造函数参数列表不全
					$names = $reflection->getConstructor()->getParameters();
					$name = $names[$idx]->getName();
					$class = $reflection->getName();

					throw new \Exception("Missing required param '$name' when instantiating DI Component '$class' !", 1);
				}
			}
		}

		return $dependencies;
	}

	/**
	 * 按顺序合并变量数组列表（索引数组）
	 * 例如：$a = ['aa', 'bb', 'cc']; $b = ['cc', 'dd']; 合并之后的 $c = ['cc', 'dd', 'cc']
	 * 
	 * @param string $component_name 组件名
	 * @param array $params 变量数组列表
	 * @return array $m_params 合并后的变量数组列表
	 */
	private function _mergeParams($component_name, $params) {
		if (empty($this->_params[$component_name])) {
			return $params;
		} elseif (empty($params)) {
			return $this->_params[$component_name];
		} else {
			$m_params = $this->_params[$component_name];

			foreach ($params as $key => $val) {

				$m_params[$key] = $val;
				
			}

			return $m_params;
		}
	}

	/**
	 * 获得组件
	 *
	 * @param string $component_name 组件名
	 * @param array $params 变量列表
	 * @return object 组件实例
	 */
	public function get($component_name, $params = array()) {
		$alias = array_search($component_name, $this->_aliases);

		// 如果已经存在该组件类的实例，则直接返回该实例的引用
		if (isset($this->_singletons[$component_name])) {
			return $this->_singletons[$component_name];
		} elseif ($alias && isset($this->_singletons[$alias])) {
			return $this->_singletons[$alias];
		}

		// 如果该组件在容器内尚未注册，说明它不依赖其他组件，可根据传入的参数直接实例化
		// 注意！这样处理是基于的前提是所有组件之间的依赖关系必须通过依赖注入实现（本容器只使用构造函数注入）
		if (!isset($this->_definitions[$component_name]) && (!$alias || !isset($this->_definitions[$alias]))) {
			$object = $this->_buildComponent($component_name, $params);

			return $object;
		}

		// 读取注册组件时的定义信息
		$c_definition = $alias ? $this->_definitions[$alias] : $this->_definitions[$component_name];

		if (is_callable($c_definition, true)) {
			// 如果是匿名函数，则获得 callable 运行需要的所有依赖（依赖必须是具体的值或引用），直接调用即可
			$params = $this->_parseDependencies($this->_mergeParams($component_name, $params));

			$object = call_user_func($c_definition, $this, $params);
		} elseif (is_object($c_definition)) {
			// 如果是对象的引用，缓存引用并直接返回
			return $this->_singletons[$component_name] = $c_definition;
		} elseif (is_array($c_definition)) {
			// 如果是数组，则是最复杂的情况：创建或获取组件类对象
			$concrete = $c_definition['class_name'];
			unset($c_definition['class_name']);

			// 合并注册组件中的参数数组与获取组件是时传入的参数数组（类型为索引数组，参数信息有先后顺序）
			if ($alias) {
				$params = $this->_mergeParams($alias, $params);
			} else {
				$params = $this->_mergeParams($component_name, $params);
			}
			

			// 如果组件名与组件类名不一致，则进入递归调用，否则直接生成或获得组件对象
			if ($concrete === $component_name) {
				// 递归出口
				$object = $this->_buildComponent($component_name, $params);
			} else {
				// 递归调用
				$object = $this->get($concrete, $params);
			}
		} else {
			throw new \Exception("Unexpected DI Component '$component_name' object definition type: " . gettype($c_definition), 1);		
		}

		// 注册为单例的，应当缓存该实例
	    if (array_key_exists($component_name, $this->_singletons)) {
	        $this->_singletons[$component_name] = $object;
	    }

	    return $object;
	}

	/**
	 * 生成组件实例
	 * 
	 * @param string $component_name 组件名称
	 * @param array $params 构造函数参数
	 * @return object 组件实例
	 */
	private function _buildComponent($component_name, $params) {
		$d = $this->_getDependencies($component_name);
		// 获得依赖信息列表
		list($reflection, $dependencies) = $this->_getDependencies($component_name);

		// 追加传入的依赖
		foreach ($params as $idx => $p) {
			$dependencies[$idx] = $p;
		}

		if (!empty($dependencies)) {
			// 依赖信息不为空，即依赖信息已经注册过或者由_buildComponent()传入
			$dependencies = $this->_parseDependencies($dependencies, $reflection);
			
			$object = $reflection->newInstanceArgs($dependencies);
		} else {
			$object = $reflection->newInstance();
		}

		return $object;
	}

	public function setValue($key, $value) {

	}

	public function getValue($key) {

	}
}

/**
 * 供依赖占位标记时使用
 */
class Instance {

    public $id;

    protected function __construct($id) {
        $this->id = $id;
    }

    public static function of($id) {
        return new static($id);
    }
}

?>