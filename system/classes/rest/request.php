<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Rest_Request library
 *
 * @package    Rest
 * @category   Rest_Request
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Request {
	/**
	 * 请求方法
	 * @var string
	 */
	public $request_method = 'GET';

	/**
	 * 输入数据
	 * @var array
	 */
	protected $_query = array();

	/**
	 * 输入数据
	 * @var array
	 */
	protected $_post = array();

	/**
	 * resource params
	 * @var array
	 */
	protected $_params = array();

	/**
	 * @var Rest_Request[]
	 */
	private static $_instances = array();

	/**
	 * 主实例
	 * @var Rest_Request
	 */
	public static $main_instance;
	/**
	 * request uri
	 * @var
	 */
	protected $_uri = '';

	/**
	 * request header
	 * @var array
	 */
	protected $_header = array();

	/**
	 * Http cache
	 * @var array
	 */
	public $cache;

	/**
	 * @var string '_list' or ''
	 */
	public $request_type = '';

	/**
	 * 请求动作
	 * @var string
	 */
	public $action = '';

	/**
	 * 请求资源的实际方法
	 * @var string
	 */
	public $method = 'get';

	/**
	 * 请求资源名
	 * @var string
	 */
	protected $resource_name;

	/**
	 * 请求资源
	 * @var Rest_Request
	 */
	public static $last_request;

	/**
	 * 请求资源
	 * @var Rest_Resource
	 */
	public static $last_resource;
	/**
	 * Are magic quotes enabled?
	 * @var Rest_Resource
	 */
	protected $magic_quotes_gpc = FALSE;

	/**
	 * get a request instance
	 * @static
	 * @param Rest_Cache|Rest_HTTP_Cache $cache
	 * @param string $uri
	 * @param string $method
	 * @param array $body
	 * @return Rest_Request
	 * @throws Rest_Exception
	 */
	public static function instance($cache = NULL, $uri = NULL, $method = NULL, $body = NULL)
	{
		if (is_null($uri))
		{
			$resource_uri = parse_url($_SERVER['REQUEST_URI']);
			$uri = $resource_uri['path'];
			$query = $_GET;
		}
		else
		{
			$resource_uri = parse_url($uri);
			$uri = $resource_uri['path'];
			isset($resource_uri['query']) ? parse_str($resource_uri['query'], $query) : ($query = array());
			// 追加到GET请求中
			$_GET += $query;
		}
		// 兼容指定index_file为空
		$base_url = Rest::$base_url . Rest::$index_file;
		if ($base_url != '/')
		{
			$uri = str_replace($base_url, '', $uri);
		}
		$uri = ltrim($uri, '/');
		if (is_null($method))
		{
			$method = strtoupper($_SERVER['REQUEST_METHOD']);
		} else {
			$method = strtoupper($method);
		}
		if (! in_array($method, array('POST', 'GET', 'PUT', 'DELETE')))
		{
			throw new Rest_Exception(Rest_Response::HTTP_METHOD_NOT_ALLOWED,
				'request method not support: ' . $method);
		}
		if ($method !== 'GET' AND is_null($body))
		{
			$body = array();
			if (strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== FALSE)
			{
				$body = $_POST;
			}
			elseif ((!$_SERVER['CONTENT_TYPE'] || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== FALSE) AND
				$_SERVER['CONTENT_LENGTH'] > 0
			)
			{
				// 输入数据为json
				$body = json_decode(file_get_contents('php://input'), TRUE);
				if ((function_exists('json_last_error') AND
					json_last_error() != JSON_ERROR_NONE)
				)
				{
					throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST, 'input data not a valid json');
				}
			}
		}

		$key = sha1($method . '~' . $uri . '?' . http_build_query($method === 'GET' ? $query : $body, NULL, '&'));
		if (! isset(self::$_instances[$key]))
		{
			self::$_instances[$key] = new Rest_Request($cache, $uri, $method, $query, $body);
			if (! isset (self::$main_instance))
			{
				self::$main_instance = self::$_instances[$key];
			}
			self::$last_request = self::$_instances[$key];
		}
		return self::$_instances[$key];
	}

	/**
	 * validate request and init input data
	 * @param Rest_Cache|Rest_HTTP_Cache $cache
	 * @param string $uri
	 * @param string $method
	 * @param array $query
	 * @param array $post
	 * @throws Rest_Exception
	 */
	public function __construct($cache, $uri, $method, $query, $post)
	{
		$this->request_method = $method;
		$this->_uri = $uri;
		$this->_query = $query;
		$this->_post = $post;
		// magic_quotes_runtime is enabled
		if (get_magic_quotes_runtime())
		{
			set_magic_quotes_runtime(0);
		}
		// magic_quotes_gpc is enabled
		if (get_magic_quotes_gpc())
		{
			$this->magic_quotes_gpc = TRUE;
		}
		if ($cache instanceof Rest_Cache)
		{
			$this->cache = Rest_HTTP_Cache::factory($cache);
		}
		elseif ($cache instanceof Rest_HTTP_Cache)
		{
			$this->cache = $cache;
		}
	}

	/**
	 * get url data
	 * @return string
	 */
	public function get_uri()
	{
		return $this->_uri;
	}

	/**
	 * get input get data
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function query($key = NULL, $default = NULL)
	{
		if (is_null($key))
		{
			return $this->_query;
		}
		else
		{
			return isset($this->_query[$key]) ? $this->clean_input_data($this->_query[$key]) : $default;
		}
	}

	/**
	 * get input post data
	 * @param string $key
	 * @param string $default
	 * @return string|array
	 */
	public function post($key = NULL, $default = NULL)
	{
		if (is_null($key))
		{
			return $this->_post;
		}
		else
		{
			return isset($this->_post[$key]) ? $this->clean_input_data($this->_post[$key]) : $default;
		}
	}

	/**
	 * Gets or sets HTTP headers to the request or response. All headers
	 * are included immediately after the HTTP protocol definition during
	 * transmission. This method provides a simple array or key/value
	 * interface to the headers.
	 *
	 * @param   mixed $key   Key or array of key/value pairs to set
	 * @param   string $value Value to set to the supplied key
	 * @return  mixed
	 */
	public function headers($key = NULL, $value = NULL)
	{
		if (! is_array($this->_header))
		{
			// Lazy load the request headers
			$this->_header = self::request_headers();
		}
		if (is_array($key))
		{
			// Act as a setter, replace all headers
			$this->_header = array_merge($this->_header, $key);
			return $this;
		}
		if ($key === NULL)
		{
			// Act as a getter, return all headers
			return $this->_header;
		}
		elseif ($value === NULL)
		{
			// Act as a getter, single header
			return Rest_Arr::get($this->_header, $key);
		}

		// Act as a setter for a single header
		$this->_header[$key] = $value;

		return $this;
	}

	public static function request_headers()
	{
		// If running on apache server
		if (function_exists('apache_request_headers'))
		{
			// Return the much faster method
			return apache_request_headers();
		}
		// If the PECL HTTP tools are installed
		elseif (extension_loaded('http'))
		{
			// Return the much faster method
			return http_get_request_headers();
		}

		// Setup the output
		$headers = array();

		// Parse the content type
		if (! empty($_SERVER['CONTENT_TYPE']))
		{
			$headers['content-type'] = $_SERVER['CONTENT_TYPE'];
		}

		// Parse the content length
		if (! empty($_SERVER['CONTENT_LENGTH']))
		{
			$headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
		}

		foreach ($_SERVER as $key => $value)
		{
			// If there is no HTTP header here, skip
			if (strpos($key, 'HTTP_') !== 0)
			{
				continue;
			}
			//not need to check these headers
			if (in_array($key, array('HTTP_COOKIE', 'HTTP_USER_AGENT', 'HTTP_AUTHORIZATION')))
			{
				continue;
			}
			// This is a dirty hack to ensure HTTP_X_FOO_BAR becomes x-foo-bar
			$headers[strtolower(str_replace(array('HTTP_', '_'), array('', '-'), $key))] = $value;
		}

		return $headers;
	}

	/**
	 * get resource params
	 * @param string $key
	 * @param string $val
	 * @return array|string|Rest_Request
	 */
	public function params($key = NULL, $val = NULL)
	{
		if (is_null($key))
		{
			return $this->_params;
		}
		else
		{
			if (is_null($val))
			{
				return isset($this->_params[$key]) ? $this->_params[$key] : NULL;
			}
			else
			{
				$this->_params[$key] = $val;
				return $this;
			}
		}
	}

	/**
	 * get request resource name
	 * @return string
	 */
	public function get_resource_name()
	{
		if (empty($this->resource_name))
		{
			$this->resource_name = Rest_Route::parse($this);
		}
		return $this->resource_name;
	}

	/**
	 * 
	 */
	public function get_request_method()
	{
		return $this->request_method;
	}
	/**
	 * 执行请求资源方法并返回响应
	 * @return Rest_Response
	 * @throws Rest_Exception
	 */
	public function exec()
	{
		if ($this->cache instanceof Rest_HTTP_Cache)
		{
			return $this->cache->execute($this);
		}
		else
		{
			return $this->execute();
		}
	}

	/**
	 * @return Rest_Response
	 * @throws Rest_Exception
	 */
	public function execute()
	{
		$class_name = 'Resource_' . str_replace('/', '_', $this->get_resource_name());
		//兼容RPC风格接口
		if ($this->action)
		{
			$this->method = $this->action;
		}
		//兼容旧客户端
		elseif (isset($this->_query['_method']))
		{
			$this->method = $this->_query['_method'];
		}
		else
		{
			$this->method = $this->request_method;
		}
		//资源与操作相同，既没有二级操作
//		if ($this->get_resource_name() == $this->action)
//		{
//			$this->method = $this->request_method;
//		}
//		else
		if ($this->request_type)
		{
			$this->method .= $this->request_type;
		}
		$this->method = strtolower($this->method);
		$response = Rest_Response::instance();
		//强制设置响应200
		if (isset($this->_query['_suppress_response_codes']) AND
			strtolower($this->_query['_suppress_response_codes']) == "true"
		)
		{
			$response->suppress_response_codes();
		}
		/** @var Rest_Resource $resource */
		$resource = new $class_name($this, $response);
		self::$last_resource = $resource;
		$resource->before();
		if (method_exists($resource, $this->method))
		{
			call_user_func(array($resource, $this->method));
		}
		else
		{
			throw new Rest_Exception(Rest_Response::HTTP_METHOD_NOT_ALLOWED,
				'request resource do not support method: ' . $this->method);
		}
		$resource->after();
		return $response;
	}

	/**
	 * Get access token
	 * @return string
	 */
	public static function get_access_token()
	{
		static $access_token;
		if (is_null($access_token))
		{
			$access_token = self::_get_access_token();
		}
		return $access_token;
	}
	
	/**
	 * 过滤反斜杠
	 * @param $string
	 * @param $force
	 * @return array
	 */
	private function addslashes_array($a){
        if(is_array($a)){
            foreach($a as $n=>$v){
                $b[$n]=$this->addslashes_array($v);
            }
            return $b;
        }else{
            return addslashes($a);
        }
    }
    
    /**
     * 清理输入数据
     * @param $str
     * @return array
     */
    private function clean_input_data($str) {
    	if (is_array($str)) {
			$new_array = array();
			foreach ($str as $key => $val) {
				$new_array[$key] = $this->clean_input_data($val);
			}
			return $new_array;
		}
    	if ($this->magic_quotes_gpc === TRUE) {
    		$str = stripslashes($str);
    	}
    	$str = $this->addslashes_array($str);
    	$str = $this->xss_clean($str);
    	//$str = htmlspecialchars($str);
    	return $str;
    }
    
    /**
     * 
     * Clean cross site scripting exploits from string.
	 * @param $data
	 * @return array
	 */
    private function xss_clean($data) {
    	if (is_array($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = $this->xss_clean($val, $tool);
			}
			return $data;
		}
    	// Fix &entity\n;
		$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

		// Remove any attribute starting with "on" or xmlns
		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

		// Remove javascript: and vbscript: protocols
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

		// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

		// Remove namespaced elements (we do not need them)
		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

		do {
			// Remove really unwanted tags
			$old_data = $data;
			$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
		} while ($old_data !== $data);
		return $data;
    }

	/**
	 * Get access token
	 * @return string
	 */
	private static function _get_access_token()
	{
		if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		}
		elseif (function_exists('apache_request_headers'))
		{
			$requestHeaders = apache_request_headers();

			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)),
				array_values($requestHeaders));

			if (isset($requestHeaders['Authorization']))
			{
				$headers = trim($requestHeaders['Authorization']);
			}
		}

		// Check that exactly one method was used
		$methodsUsed = ! empty($headers) + isset($_GET['access_token']) + isset($_POST['access_token']);
		if ($methodsUsed > 1)
		{
			return '';
		}
		elseif ($methodsUsed == 0)
		{
			return '';
		}

		// HEADER: Get the access token from the header
		if (! empty($headers))
		{
			if (! preg_match('/Bearer\s(\S+)/', $headers, $matches))
			{
				return '';
			}

			return $matches[1];
		}

		// POST: Get the token from POST data
		if (isset($_POST['access_token']))
		{
			if ($_SERVER['REQUEST_METHOD'] != 'POST')
			{
				return '';
			}

			// IETF specifies content-type. NB: Not all webservers populate this _SERVER variable
			if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] != 'application/x-www-form-urlencoded')
			{
				return '';
			}

			return $_POST['access_token'];
		}

		// GET method
		return $_GET['access_token'];
	}

	public static function get_request_log()
	{
		if (! self::$main_instance)
		{
			return array('request' => array(
				'access_token' => self::get_access_token(),
				'uri'          => $_SERVER['REQUEST_URI'],
				'method'       => strtoupper($_SERVER['REQUEST_METHOD']),
				'query'        => $_GET,
				'post'         => file_get_contents('php://input'),
			)
			);
		}
		else
		{
			if (self::$main_instance == self::$last_request)
			{
				return array('request' => array(
					'access_token' => self::$last_request->get_access_token(),
					'uri'          => self::$last_request->get_uri(),
					'method'       => self::$last_request->request_method,
					'query'        => self::$last_request->query(),
					'post'         => self::$last_request->post(),
				)
				);
			}
			else
			{
				return array(
					'batch_request' => array(
						'access_token' => self::$main_instance->get_access_token(),
						'uri'          => self::$main_instance->get_uri(),
						'method'       => self::$main_instance->request_method,
						'query'        => self::$main_instance->query(),
						'post'         => self::$main_instance->post(),
					),
					'request'       => array(
						'access_token' => self::$last_request->get_access_token(),
						'uri'          => self::$last_request->get_uri(),
						'method'       => self::$last_request->request_method,
						'query'        => self::$last_request->query(),
						'post'         => self::$last_request->post(),
					)
				);
			}
		}
	}
}
