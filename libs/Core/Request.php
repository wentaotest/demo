<?php namespace June\Core;

class Request {

	private $_host_info = null;

	private $_headers = null;

	private $_params = null;

	private $_cookies = null;

	public function isSecure() {
		return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1) 
		|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
	}

	public function getClientIp() {
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	public function getServerIp() {
		return gethostbyname($this->getHostName());
	}

	public function method() {
		if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
			return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		} else {
			return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		}
	}

	public function isGet() {
		return $this->method() === 'GET' ? true : false;
	}

	public function isPost() {
		return $this->method() === 'POST' ? true : false;
	}

	public function isAjax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}

	public function getHostName() {
		if (isset($_SERVER['HTTP_HOST'])) {
			$host = explode(":", $_SERVER['HTTP_HOST']);
			return $host[0];
		} else {
			return $_SERVER['SERVER_NAME'];
		}
	}

	public function getHostPort() {
		return (int) $_SERVER['SERVER_PORT'];
	}

	public function getHostInfo() {
		if ($this->_host_info === null) {
            $http = $this->isSecure() ? 'https' : 'http';
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_host_info = $http . '://' . $_SERVER['HTTP_HOST'];
            } else {
                $this->_host_info = $http . '://' . $_SERVER['SERVER_NAME'];
                $port = $this->getHostPort();
                if ($port !== 80) {
                	$this->_host_info .= ':' . $port;
                }
            }
        }

        return $this->_host_info;
	}

	public function getHeaderers() {
		if ($this->_headers === null) {
			if (function_exists('getallheaders')) {
				$headers = getallheaders();
			} elseif (function_exists('http_get_request_headers')) {
				$headers = http_get_request_headers();
			} else {
				foreach ($_SERVER as $key => $value) {
					if (starts_with($key, 'HTTP_')) {
						$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value; 
					}
				}
			}
		}

		$this->_headers = $headers;

		return $this->_headers;
	}

	public function getHeader($name, $default_val = null) {
		if ($this->_headers === null) {
			$headers = $this->getHeaderers();
		} else {
			$headers = $this->_headers();
		}

		return isset($headers[$name]) ? $headers[$name] : $default_val;
	}

	public function getUrl() {
		// 支持IIS
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
			$url = $_SERVER['HTTP_X_REWRITE_URL'];
		// APACHE、NGINX
		} elseif (isset($_SERVER['REQUEST_URI'])) {
			$url = $_SERVER['REQUEST_URI'];
			if ($url !== '' && !starts_with($url, '/')) {
				$url = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $url);
			}
		// IIS 5.0 CGI
		} elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
			$url = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $url .= '?' . $_SERVER['QUERY_STRING'];
            }
		} else {
			throw new Exception("Unknown Request URL!", 1);
		}

		return $url;
	}

	public function getFullUrl() {
		return $this->getHostInfo() . $this->getUrl();
	}

	public function getQueryString() {
		return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	}

	/**
	 * 返回包含$_GET, $_POST和$_COOKIE等参数数组
	 */
	public function getParams() {
		if ($this->_params === null) {
			$this->_params = $_REQUEST;
		}

		return $this->_params;
	}

	public function getParam($name, $default_val = null) {
		$params = $this->getParams();

		return isset($this->_params[$name]) ? $this->_params[$name] : $default_val;
	}

	public function getCookies() {
		if ($this->_cookies === null) {
			$this->_cookies = $_COOKIE;
		}

		return $this->_cookies;
	}

	public function getCookie($name, $default_val = null) {
		$cookies = $this->getCookies();

		return isset($cookies[$name]) ? $cookies[$name] : $default_val;
	}

	public function getCsrfToken() {

	}
}

?>