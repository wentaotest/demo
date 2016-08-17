<?php

use June\Core\Cache\RedisClient;
use June\Core\Cache\MemcacheClient;
use June\Core\JuneException;
use June\Core\DIContainer;

/**
 * 运行模式
 * 
 * @param string $mode
 * @author Vincent An
 */
function june_run_mode($mode) {
	switch ($mode) {
		case 'web_app':
			// 捕获web_app模式下PHP错误
			register_shutdown_function('catch_web_app_php_error');
			break;
		case 'web_api':
			// 捕获web_api模式下PHP错误
			register_shutdown_function('catch_web_api_php_error');
			break;
		case 'bck_cli':
			// 捕获bck_cli模式下PHP错误
			register_shutdown_function('catch_bck_cli_php_error');
			break;
	}
}

/**
 * 网站应用（web_app）运行模式下PHP错误处理函数（但无法捕获解析错误，如括号未闭合等标点符号问题）
 * 
 * @author 安佰胜
 */
function catch_web_app_php_error() {
	$last_error = error_get_last();
	
	if (!empty($last_error)) {
		$err_vars = array('type' => $last_error['type'],
						  'message' => $last_error['message'],
						  'file' => $last_error['file'],
						  'line' => $last_error['line'],
						  'trace' => debug_backtrace(),
		);
		
		extract($err_vars);
		
		@ob_end_clean();
		ob_start();
        $error_page_path = dirname(__FILE__) . '/../Core/Exception/error_page.php';
		include $error_page_path;
		ob_end_flush();
	}
}

/**
 * 网站接口（web_api）运行模式下PHP错误处理函数（但无法捕获解析错误，如括号未闭合等标点符号问题）
 *
 * @author 安佰胜
 */
function catch_web_api_php_error() {
	$last_error = error_get_last();

	if (!empty($last_error)) {
		$type    = $last_error['type'];
		$message = $last_error['message'];
		$file    = $last_error['file'];
		$line    = $last_error['line'];
		$trace   = debug_backtrace();

		@ob_end_clean();
		ob_start();
		
		$ret_msg = array(
				'action' => '',
				'succeeded' => false,
				'result' => array(
						'token' => 'this is a token!',
				),
				'errmsg' => "服务器语法错误：" . $message,
				'errcode' => intval($type),
		);
		
		echo json_encode($ret_msg);
		
		ob_end_flush();
	}
}

/**
 * 后台脚本（bck_cli）运行模式下PHP错误处理函数（但无法捕获解析错误，如括号未闭合等标点符号问题）
 *
 * @author 安佰胜
 */
function catch_bck_cli_php_error() {
	$last_error = error_get_last();

	if (!empty($last_error)) {
		$type    = $last_error['type'];
		$message = $last_error['message'];
		$file    = $last_error['file'];
		$line    = $last_error['line'];
		$trace   = debug_backtrace();

		echo "\n".
			 "+++++++++++++++++++++++++++++++++++++\n".
			 "+     [ Catched PHP Error !!! ]     +\n".
			 "+++++++++++++++++++++++++++++++++++++\n";
		
		echo "| Error Type    | " . JuneException::getPhpErrName($type) . "\n";
		echo "| Error Message | " . JuneException::getPhpErrHint($type) . "\n";
		echo "| File Name     | $file\n";
		echo "| Error Line    | $line\n";
		echo "+++++++++++++++++++++++++++++++++++++\n";
		
		if (!empty($trace) && is_array($trace)) {
			foreach($trace as $k => $p) {
				$class = isset($p['class']) ? $p['class'] : '';
				$func  = isset($p['function']) ? $p['function'] : '';
				$line  = isset($p['line']) ? $p['line'] : '';
				$file  = isset($p['file']) ? $p['file'] : '';
				
				echo $k . " File:" . $file . " Line:" . $line . " Class:" . $class . " Func:" . $func . "\n";
			}
		}
		
	}
}

/**
 * 获取配置文件 【注意】：若配置项不存在，则返回null，用以区分值为false的配置项
 *
 * @author 安佰胜
 */
function C($cfg_file_name, $cfg_key = null) {
    $cfg_reg = June\Core\Registry\ConfigRegistry::getInstance();

    if (!is_null($cfg_reg->get($cfg_file_name))) {
        $cfg = $cfg_reg->get($cfg_file_name);
    } else {
        $cfg_file_path = "./configs/" . $cfg_file_name . ".php";

        if (file_exists($cfg_file_path)) {
            $cfg = require_once($cfg_file_path);

            $cfg_reg->set($cfg_file_name, $cfg);
        } else {
            return NULL;
        }
    }

    if (is_null($cfg_key)) {
        return $cfg;
    } else {
        if (strstr($cfg_key, '.')) {
            $cfg_val = $cfg;
            $crumbs = explode('.', $cfg_key);
            foreach ($crumbs as $key_name) {
                $cfg_val = isset($cfg_val[$key_name]) ? $cfg_val[$key_name] : NULL;
            }
        } else {
            $cfg_val = isset($cfg[$cfg_key]) ? $cfg[$cfg_key] : NULL;
        }

        return $cfg_val;
    }
}

/**
 * 判断字符串是否以某个子串开头
 *
 * @author 安佰胜
 */
function starts_with($haystack, $needle, $case_sensitive = true) {
    if (!$case_sensitive) {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
    }

    return (substr($haystack, 0, strlen($needle)) === $needle);
}

/**
 * 判断字符串是否以某个子串结束
 *
 * @author 安佰胜
 */
function ends_with($haystack, $needle, $case_sensitive = true) {
    if (!$case_sensitive) {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
    }

    if ($len = strlen($needle)) {
        return true;
    } 
    
    return (substr($haystack, -$len) === $needle);
}

function xr($param_name, $default = null, $trim = ' ') {
    if (isset($_REQUEST[$param_name]) && !empty($_REQUEST[$param_name])) {
        $p = $_REQUEST[$param_name];

        if (is_string($p)) {
            return trim($p, $trim);
        }

        return $p;
    } else {
        return $default;
    }
}

function xp($param_name, $default = null, $trim = ' ') {
    if (isset($_POST[$param_name])) {
        $p = $_POST[$param_name];

        if (!is_array($p) && !is_object($p)) {
            return trim($p, $trim);
        } else {
        	return $p;
        }
    } else {
        return $default;
    }
}

function xg($param_name, $default = null, $trim = ' ') {
    if (isset($_GET[$param_name])) {
        $p = $_GET[$param_name];

        if (!is_array($p) && !is_object($p)) {
            return trim($p, $trim);
        } else {
        	return $p;
        }
    } else {
        return $default;
    }
}

function xpn($param_name, $default = null) {
    $val = xp($param_name, $default);
    return intval($val);
}

function xgn($param_name, $default = null) {
    $val = xg($param_name, $default);
    return intval($val);
}

function xrn($param_name, $default = null) {
	$val = xr($param_name, $default);
	return intval($val);
}

/**
 * 渲染视图
 * 
 * @param string $view_path
 * @param array $vars
 * 
 * @author 安佰胜
 */
function render_view($view_path, $vars=null) {
    if (is_string($vars)) {
        parse_str($vars);
    } else if (is_array($vars)) {
        extract($vars);
    }

    $view_path = str_replace('\\', '/', $view_path);
    
    ob_start();
    include($view_path);
    $buffer = ob_get_contents();
    ob_end_clean();
    
    $last_error = error_get_last();
    if (empty($last_error)) {
    	echo $buffer;
    }
}

/**
 * 简易curl请求
 * 
 * @param array $options
 * @return mixed|unknown
 * 
 * @author 安佰胜
 */
function curl($options) {
	$ch = curl_init();
	
	$method    = strtoupper($options['method']);
	$header    = isset($options['header']) ? $options['header'] : array();
	$data_type = isset($options['data_type']) ? strtolower($options['data_type']) : 'text';
	
	curl_setopt($ch, CURLOPT_URL, $options['url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
	curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	
	if ($method == 'GET') {
		curl_setopt($ch, CURLOPT_HEADER, 0);
	} elseif ($method == 'POST') {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post_data']);
	}
	
	$output = curl_exec($ch);
	
	// 检查是否有错误发生
	if (curl_errno($ch)) {
		$err_msg = curl_error($ch);
		throw new JuneException('CURL请求错误！原因：' . $err_msg);
	}
	
	switch ($data_type) {
		case 'json':
			$result = json_decode($output, true);
				
			if (json_last_error() !== JSON_ERROR_NONE) {
				$result = false;
			}
				
			break;
				
		case 'xml':
			$result = xml_to_array($output);
				
			break;
		
		default:
			$result = $output;
			
			break;
	}
	
	
	curl_close($ch);
	
	return $result;
}

/**
 * 支持https类型的curl请求
 * 
 * @param string $url
 * @param array $data
 * @param array $certs
 * @param string $response_format
 * @param integer $time_out
 * @param array $header
 * @return boolean|string
 * 
 * @author Vincent An
 */
function curl_ssl($url, $data, $certs, $response_format = 'json', $time_out = 30, $header = array()) {
	$ch = curl_init();
	
	// 设置超时时间
	curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	// 设置请求地址
	curl_setopt($ch, CURLOPT_URL, $url);
	
	// 设置SSL验证
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
	// 设置证书
	curl_setopt($ch, CURLOPT_SSLCERT, $certs['api_cert']);
	curl_setopt($ch, CURLOPT_SSLKEY, $certs['api_key']);
	curl_setopt($ch, CURLOPT_CAINFO, $certs['rootca']);
	
	// 设置HEADER信息
	if (count($header) >= 1) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	}
	
	// 设置提交数据
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$output = curl_exec($ch);
	
	$response_format = strtolower($response_format);
	switch ($response_format) {
		case 'json':
			$result = json_decode($output, true);
			
			if (json_last_error() !== JSON_ERROR_NONE) {
				$result = $output;
			} else {
				$result = false;
			}
			
			break;
			
		case 'xml':
			$result = xml_to_array($output);
			
			break;
	}
	
	
	curl_close($ch);
	
	return $result;
}

/**
 * 获得AppsDbConnPool数据库连接池类实例（兼容老代码，不推荐使用）
 *
 * @return \June\apps\AppsDbConnPool
 *
 * @author 安佰胜
 */
function june_get_apps_db_connection() {
	return June\apps\AppsDbConnPool::getInstance();
}

/**
 * 获得AppsDbConnPool数据库连接池类实例（替代june_get_apps_db_connection，推荐使用）
 *
 * @return \June\apps\AppsDbConnPool
 *
 * @author 安佰胜
 */
function june_get_apps_db_conn_pool() {
	return June\apps\AppsDbConnPool::getInstance();
}

/**
 * 关闭AppsDbConnPool数据库连接池中的所有连接
 * 
 * @author Vincent An
 */
function june_close_apps_db_conn_pool() {
	$db_pool = june_get_apps_db_conn_pool();
	
	$db_pool->closeMongoDbConn();
	$db_pool->closeMySqlDbConn();
}

/**
 * 获取MongoDB连接类实例
 * 
 * @return \June\Core\DbConn\MongoDbConn
 * 
 * @author Vincent An
 */
function june_get_mongo_db_conn() {
	return june_get_apps_db_conn_pool()->getMongoDbConnInst();
}

/**
 * 获取MySqlDB连接类实例
 * 
 * @return \June\Core\DbConn\MysqlDbConn
 * 
 * @author Vincent An
 */
function june_get_mysql_db_conn() {
	return june_get_apps_db_conn_pool()->getMySqlDBConnInst();
}

/**
 * 获取缓存客户端实例，并建立连接
 * 
 * @throws JuneException
 * 
 * @author 安佰胜
 */
function june_get_cache_client() {
	$cache_type = C('cache_conf', 'cache_type');
	$cfg = C('cache_conf', "$cache_type");
	
	$cache = null;
	switch ($cache_type) {
		case 'redis':	
			$cache = RedisClient::getInstance($cfg);
			break;
		case 'memcache':
			$cache = MemcacheClient::getInstance($cfg);
			break;
	}
	
	if (empty($cache)) {
		throw new JuneException("缓存对象创建失败，请检查缓存配置文件！");
	}
	
	$is_persistent = C('cache_conf', 'persistent_conn');
	
	if ($is_persistent) {
		$cache->pconnect();
	} else {
		$cache->connect();
	}
	
	return $cache;
}

/**
 * 获取消息队列
 * 
 * @return June\Core\Queue\JuneQueue
 * 
 * @author 安佰胜
 */
function june_get_queue() {
	$di = DIContainer::getInstance();
	
	$queue = $di->get('queue', array(C('web_conf', 'queue.beanstalk')));
	
	return $queue;
}

/**
 * 关闭消息队列连接
 * 
 * @author Vincent An
 */
function june_close_queue() {
	$di = DIContainer::getInstance();
	
	$queue = $di->get('queue', array(C('web_conf', 'queue.beanstalk')));
	
	return $queue->disconnect();
}

/**
 * 生成URL中完整的action参数
 * 
 * @param string $act
 * @return string
 * 
 * @author 安佰胜
 */
function june_gen_full_action($act) {
	// 获取路由信息
	$di = DIContainer::getInstance();
	$router = $di->get('router');
	$routes = $router->getRoutes();
	$module = $routes['module'];
	$ctr_nm = $routes['controller'];
	
	// 处理路由参数
	if (strstr($act, '.')) {
		$crumbs = explode('.', $act);
	
		if (count($crumbs) == 3) {
			$module = $crumbs[0];
			$ctr_nm = $crumbs[1];
			$act    = $crumbs[2];
		} elseif (count($crumbs) == 2) {
			$ctr_nm = $crumbs[0];
			$act    = $crumbs[1];
		}
	}
	
	return $module . '.' . $ctr_nm . '.' . $act;
}

/**
 * 异常错误打印页面
 * 
 * @param \Exception $exception
 * 
 * @author 安佰胜
 */
function error_page($exception) {
	$html = "<!DOCTYPE html>" .
			"<html>" .
			"	<head>" .
			"		<title>错误异常</title>" .
			"		<meta http-equiv='content-type' content='text/html;charset=utf-8'>" .
			"	</head>" .
			"	<body>" .
			"		<label>错误信息：<div>" .
			$exception->getMessage() . 
			"		</div></label>" .
			"		<label>所在文件：<div>" .
			$exception->getFile() .
			"		</div></label>" .
			"		<label>所在行号：<div>" .
			$exception->getLine() .
			"		</div></label>" .
			'<a href="javascript:history.go(-1)">后退</a>' .
			"	</body>" .
			"</html>";

			echo $html;
}

/**
 * 设置开发调试模式
 * 
 * @param string $mode_name
 * 
 * @author 安佰胜
 */
function debug_mode($mode_name) {
	
	switch ($mode_name) {
		case 'development':
			$log_file = __ROOT__ . "/logs/dev_june.log";
			
			if (!file_exists($log_file)) {
				
				@touch($log_file);
				
				if (file_exists($log_file)) {
					@chmod($log_file, 0622);
				}
				
			}
			
			ini_set("log_errors", "1"); // 启用日志记录
			// ini_set("error_log", $log_file); // 日志文件, 路径相对于该文件
			ini_set("display_errors", "On"); // 给客户端显示错误信息
			
			error_reporting(E_ALL);
			
			break;
		case 'production':
			$log_file = __ROOT__ . "/logs/pro_june.log";
				
			if (!file_exists($log_file)) {
				
				@touch($log_file);
				
				if (file_exists($log_file)) {
					@chmod($log_file, 0622);
				}
			}
			
			ini_set("log_errors", "1"); // 启用日志记录
			ini_set("error_log", $log_file); // 日志文件, 路径相对于该文件
			ini_set("display_errors", "Off"); // 给客户端显示错误信息
			
			error_reporting(~E_ALL);
			break;
		default:
			break;
	}
}

/**
 * 对中文更加友好的json_encode方案
 * 
 * @param array $value
 * @param boolean $is_pretty
 * @return string
 * 
 * @author 安佰胜
 */
function json_encode_ex($value, $is_pretty = false) {
	$json_str = "";
	
	if (version_compare(PHP_VERSION, '5.4.0', '<')) {
		$str = json_encode($value);
		$str = preg_replace_callback(
				"#\\\u([0-9a-f]{4})#i",
				function($matchs) {
					return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
				},
				$str);
		if ($is_pretty) {
			$json_str = pretty_print_json($str);
		} else {
			$json_str = $str;
		}
	} else {
		if ($is_pretty) {
			$json_str = json_encode($value, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		} else {
			$json_str = json_encode($value, JSON_UNESCAPED_UNICODE);
		}
	}
	
	return $json_str;
}

/**
 * 格式化输出json字符串
 * 
 * @param string $json
 * @return string
 * 
 * @author 安佰胜
 */
function pretty_print_json($json) {
    $result = '';
    $tab = 0;
    $length = strlen($json);
    $indent = isset($indent) ? $indent : '    ';
    $new_line = "\n";
    $prev_char = '';
    $out_of_quotes = true;
      
    for ($i = 0; $i <= $length; $i++) {
        $char = substr($json, $i, 1);
      
        if ($char == '"' && $prev_char != '\\') {
            $out_of_quotes = !$out_of_quotes;
        } else if (($char == '}' || $char == ']') && $out_of_quotes) {
            $result .= $new_line;
            $tab--;

            for ($j = 0; $j < $tab; $j++) {
                $result .= $indent;
            }
        }
        $result .= $char;
              
        if (($char == ',' || $char == '{' || $char == '[') && $out_of_quotes) {
            $result .= $new_line;
            if ($char == '{' || $char == '[') {
                $tab++;
            }  
      
            for ($j = 0; $j < $tab; $j++) {
                $result .= $indent;
            }
        }
        	
        $prev_char = $char;
    }
    
	return $result;
}

/**
 * 获取客户端IP地址
 * 
 * @author 安佰胜
 */
function get_client_ip($type = 0) {
	$type = $type ? 1 : 0;
	
	$ip = null;
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$pos = array_search('unknown',$arr);
		if(false !== $pos) unset($arr[$pos]);
		$ip = trim($arr[0]);
	} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	
	// 验证IP地址合法验证
	$long = ip2long($ip);
	$ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
	
	return $ip[$type];
}

/**
 * 获取服务器地址
 * 
 * @author Vincent An
 */
function get_server_ip() {
	$server_ip = '127.0.0.1';
	
	if (isset($_SERVER)) {
		if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR']) {
			$server_ip = $_SERVER['SERVER_ADDR'];
		} elseif (isset($_SERVER['LOCAL_ADDR']) && $_SERVER['LOCAL_ADDR']) {
			$server_ip = $_SERVER['LOCAL_ADDR'];
		}
	} else {
		$server_ip = getenv('SERVER_ADDR');
	}
	
	return $server_ip;
}

/**
 * 获取省份名称拼音缩写
 * 
 * @author 安佰胜
 */
function get_prov_name_abbr($prov_name) {
	$arr = array(
		'AH' => '安徽',
		'BJ' => '北京',
		'FJ' => '福建',
		'GS' => '甘肃',
		'GD' => '广东',
		'GX' => '广西',
		'GZ' => '贵州',
		'HI' => '海南',
		'HE' => '河北',
		'HA' => '河南',
		'HL' => '黑龙江',
		'HB' => '湖北',
		'HN' => '湖南',
		'JL' => '吉林',
		'JS' => '江苏',
		'JX' => '江西',
		'LN' => '辽宁',
		'NM' => '内蒙古',
		'NX' => '宁夏',
		'QH' => '青海',
		'SD' => '山东',
		'SX' => '山西',
		'SN' => '陕西',
		'SH' => '上海',
		'SC' => '四川',
		'TJ' => '天津',
		'XZ' => '西藏',
		'XJ' => '新疆',
		'YN' => '云南',
		'ZJ' => '浙江',
		'CQ' => '重庆',
		'MC' => '澳门',
		'HK' => '香港',
		'TW' => '台湾',
		'GW' => '国外');

	return array_search($prov_name, $arr);
}

/**
 * 随机生成指定长度的字符串
 * @param $length 字符串长度
 * @param $type 字符串类型：0：纯数字 1：数字+小写字母  2：数字+大小写字符
 * @author 李优
 */
function getCode($length = 10, $type = 0){
	$str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$t = array(9,35,strlen($str)-1);
	
	//随机生成字符串所需内容
	$s = "";
	for($i=0;$i<$length;$i++){
		$s .= $str[rand(0,$t[$type])];
	}
	return $s;
}

/**
 * 向字符串中指定位置插入子串
 * 
 * @param string $substr
 * @param integer $pos
 * @param string $str
 * @return string
 * 
 * @author 安佰胜
 */
function insert_to_string($substr, $pos, $str) {
	//指定插入位置前的字符串
	$start_str = "";
	for($j=0; $j<$pos; $j++){
		$start_str .= $str[$j];
	}
	
	//指定插入位置后的字符串
	$last_str="";
	for ($j=$pos; $j<strlen($str); $j++){
		$last_str .= $str[$j];
	}
	
	//将插入位置前，要插入的，插入位置后三个字符串拼接起来
	$str = $start_str . $substr . $last_str;
	
	//返回结果
	return $str;
}

/**
 * 检查字符大小写
 * 
 * @param string $char
 * @return number|boolean false-输入值不是字母，1-小写，2-大写
 * 
 * @author 安佰胜
 */
function check_case(string $char) {
	$ascii_code = ord($char);
	
	// 输入值为小写
	if ($ascii_code > 96 && $ascii_code < 123) {
		return 1;
	}
	
	// 输入值为大写
	if ($ascii_code > 64 && $ascii_code < 91) {
		return 2;
	}
	
	// 输入值不是字母
	return false;
}

/**
 * 将字符串转为驼峰风格
 * 
 * @param string $var_nm
 * @param string $search
 * @return string
 * 
 * @author 安佰胜
 */
function camp_case($var_nm, $search = '_') {
	$crumbs = explode($search, $var_nm);
	
	$temp = "";
	if (!empty($crumbs) && is_array($crumbs)) {
		foreach ($crumbs as $c) {
			$temp .= ucfirst($c);
		}
	}
	
	return lcfirst($temp);
}

/**
 * 将字符串转为下划线风格
 * 
 * @param string $var_nm
 * @return string
 * 
 * @author 安佰胜
 */
function underscore_case($var_nm) {
	$crumbs = array();
	
	$temp = '';
	for ($i = 0; $i < strlen($var_nm); $i++) {
		$str_case = check_case($var_nm[$i]);
		
		switch ($str_case) {
			case 1:
				$temp .= $var_nm[$i];
				break;
			case 2:
				$crumbs[] = $temp;
				$temp = strtolower($var_nm[$i]);
				break;
			default:
		}
	}
	
	if (!empty($temp)) {
		$crumbs[] = $temp;
	}
	
	return !empty($crumbs) ? implode('_', $crumbs) : '';
	
}

/**
 * 将数组转化为xml
 * 
 * @param array $array
 * @return string|boolean
 * 
 * @author Vincent An
 */
function array_to_xml($array) {
	$xml = "<xml>";
	foreach ($array as $key => $val) {
		if (is_array($val)) {
			return false;
		}
		
		if (is_numeric($val)) {
			$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
		} else {
			$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
		}
	}
	$xml .= "</xml>";
	return $xml;
}

/**
 * 将xml转为数组
 * 
 * @param string $xml
 * @return mixed
 * 
 * @author Vincent An
 */
function xml_to_array($xml) {
	$std_arr = (array)(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
	
	return json_decode(json_encode($std_arr), TRUE);
}

/**
 * 将关联数组转化为Query string字符串
 * 
 * @param array $params
 * @throws JuneException
 * @return string
 * 
 * @author Vincent An
 */
function array_to_query_string($params) {
	if (!is_assoc($params)) {
		throw new JuneException('转化为Query String的参数必须为关联数组！', JuneException::REQ_ERR_PARAMS_INVALID);
	}
	
	$q_str = "";
	foreach ($params as $name => $val) {
		if ($name != 'sign' && $val != '' && !is_array($val)) {
			$q_str .= $name . '=' . $val . '&';
		}
	}
	
	return trim($q_str, '&');
}

/**
 * 判断是否为关联数组
 * 
 * @param array $array
 * @return boolean
 * 
 * @author Vincent An
 */
function is_assoc($array) {
	if (!is_array($array)) {
		return false;
	}
	
	return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * 记录支付相关日志
 *
 * @param string $word 信息内容
 * @param string $filename 日志文件名
 * @author 李优
 */
function pay_log($word = '',$filename = 'log.log') {
	ini_set("log_errors", "1"); // 启用日志记录
	ini_set("error_log", __ROOT__ . "/logs/".date("Ymd_", time()).$filename); // 日志文件, 路径相对于该文件
	 
	error_log($word);
}

/**
 * 生成fastdfs存储图片的绝对地址
 * 
 * @param string $file_name
 * @return string
 * 
 * @author Vincent An
 */
function fdfs_image_url($file_name) {
	$full_path = C('web_conf', 'host_name') . $file_name;
	
	return $full_path;
}
