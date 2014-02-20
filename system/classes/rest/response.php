<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Rest_Response library
 *
 * @package    Rest
 * @category   Rest_Response
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Response_Exception extends Rest_Exception {
}

class Rest_Response {

	/**
	 * HTTP status codes for successful and error states as specified by draft 20.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4.1.2
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-5.2
	 */
	const HTTP_FOUND = 302;
	const HTTP_BAD_REQUEST = 400;
	const HTTP_UNAUTHORIZED = 401;
	const HTTP_FORBIDDEN = 403;
	const HTTP_NO_FOUND = 404;
	const HTTP_UNAVAILABLE = 503;

	const HTTP_OK = 200;
	const HTTP_SEE_OTHER = 303;
	const HTTP_NOT_MODIFIED = 304;
	const HTTP_METHOD_NOT_ALLOWED = 405;
	const HTTP_CONFLICT = 409;
	const HTTP_SERVER_ERROR = 500;
	const HTTP_BAD_GATEWAY = 502;
	const HTTP_GATEWAY_TIMEOUT = 504;

	/**
	 * Rest_Response http header
	 * @var array
	 */
	protected $_header = array();

	/**
	 * Rest_Response content
	 * @var string
	 */
	protected $_body = '';

	/**
	 * Rest_Response http code
	 * @var int
	 */
	protected $_status = Rest_Response::HTTP_OK;

	/**
	 * Is force set response http code to 200
	 * @var bool
	 */
	protected $_is_suppress_response_codes = FALSE;

	/**
	 * @var array
	 */
	protected $_messages = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);

	/**
	 * @var Rest_Response
	 */
	private static $_instance;

	/**
	 * @static
	 * @return Rest_Response
	 */
	public static function instance()
	{
		if (! self::$_instance)
		{
			self::$_instance = new Rest_Response();
		}
		return self::$_instance;
	}

	/**
	 * Gets and sets headers to the [Response], allowing chaining
	 * of response methods. If chaining isn't required, direct
	 * access to the property should be used instead.
	 *
	 *       // Get a header
	 *       $accept = $response->headers('Content-Type');
	 *
	 *       // Set a header
	 *       $response->headers('Content-Type', 'text/html');
	 *
	 *       // Get all headers
	 *       $headers = $response->headers();
	 *
	 *       // Set multiple headers
	 *       $response->headers(array('Content-Type' => 'text/html', 'Cache-Control' => 'no-cache'));
	 *
	 * @param mixed $key
	 * @param string $value
	 * @return string|Rest_Response|array
	 */
	public function headers($key = NULL, $value = NULL)
	{
		if ($key === NULL)
		{
			return $this->_header;
		}
		elseif (is_array($key))
		{
			$this->_header = array_merge($this->_header, $key);
			return $this;
		}
		elseif ($value === NULL)
		{
			return Rest_Arr::get($this->_header, $key);
		}
		else
		{
			$this->_header[$key] = $value;
			return $this;
		}
	}

	/**
	 * 设置响应码
	 * @param int $status HTTP状态马
	 * @return Rest_Response
	 * @throws Rest_Response_Exception
	 */
	public function set_status($status)
	{
		if (! array_key_exists($status, $this->_messages))
		{
			throw new Rest_Response_Exception(Rest_Response::HTTP_SERVER_ERROR, 'invalid status code:' . $status);
		}
		$this->_status = $status;
		return $this;
	}

	/**
	 * 获取响应码
	 * @return string
	 */
	public function get_status()
	{
		return $this->_status;
	}

	/**
	 * 获取响应消息
	 * @return string
	 */
	public function get_body()
	{
		return $this->_body;
	}

	/**
	 * 设置ETAG
	 * @param string $etag
	 * @return Rest_Response
	 */
	public function add_etag($etag)
	{
		$this->_header['etag'] = $etag;
		return $this;
	}

	/**
	 * 设置缓存时间
	 * @param int $time
	 * @return Rest_Response
	 */
	public function add_cache($time = 86400)
	{
		if ($time)
		{
			$this->_header['cache-control'] = 'max-age=' . $time . ', must-revalidate';
		}
		else
		{
			$this->_header['cache-control'] = 'no-cache';
		}
		return $this;
	}

	/**
	 * ETAG未修改直接返回304
	 * @param string $etag
	 * @return Rest_Response
	 */
	public function if_match($etag)
	{
		if (! empty($_SERVER['HTTP_IF_MATCH']))
		{
			$if_match = trim($_SERVER['HTTP_IF_MATCH']);
			if ($if_match == '*' || $if_match == $etag)
			{
				header('Status: 304 ' . $this->_messages[304]);
				exit;
			}
		}
		return $this;
	}

	/**
	 * ETAG未修改直接返回304
	 * @param string $etag
	 * @return Rest_Response
	 */
	public function if_none_match($etag)
	{
		if (! empty($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			$if_none_match = trim($_SERVER['HTTP_IF_NONE_MATCH']);
			if ($if_none_match == '*' || $if_none_match == $etag)
			{
				header('Status: 304 ' . $this->_messages[304]);
				exit;
			}
		}
		return $this;
	}

	/**
	 * 设置响应消息
	 * @param array|string $data
	 * @return Rest_Response
	 */
	public function set_body($data)
	{
		$this->_body = $data;
		return $this;
	}

	/**
	 * Set force response http code to 200
	 */
	public function suppress_response_codes()
	{
		$this->_is_suppress_response_codes = TRUE;
	}

	/**
	 * 输出结果
	 */
	public function output()
	{
		if (! isset($this->_header['content-type']))
		{
			$this->_header['content-type'] = Rest::$content_type . ';charset=' . Rest::$charset;
		}

		if ($this->_is_suppress_response_codes)
		{
			if (! is_array($this->_body))
			{
				$this->_body = array('data' => $this->_body);
			}
			$this->_body = array('response_code' => $this->_status) + $this->_body;
			$this->_status = Rest_Response::HTTP_OK;
		}
		if ($this->_status == Rest_Response::HTTP_FOUND)
		{
			$this->_body = '';
		}

		if (is_array($this->_body))
		{
			$this->_body = json_encode($this->_body);
		}
		$this->_header['content-length'] = strlen($this->_body);
		header('Status:' . $this->_status . ' ' . $this->_messages[$this->_status]);
		foreach ($this->_header as $key => $val)
		{
			header(self::ucfirst($key) . ':' . $val);
		}
		echo $this->_body;
	}

	/**
	 * Uppercase words that are not separated by spaces, using a custom
	 * delimiter or the default.
	 *
	 *      $str = Rest_Response::ucfirst('content-type'); // returns "Content-Type"
	 *
	 * @static
	 * @param $string $string string to transform
	 * @param string $delimiter delemiter to use
	 * @return string
	 */
	public static function ucfirst($string, $delimiter = '-')
	{
		// Put the keys back the Case-Convention expected
		return implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
	}
}
