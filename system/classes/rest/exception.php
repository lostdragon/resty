<?php defined('SYS_PATH') or die('No direct access');
/**
 * Rest exception class. Translates exceptions using the [I18n] class.
 *
 * @package    System
 * @category   Exceptions
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Exception extends Exception {

	/**
	 * @var  array  PHP error code => human readable name
	 */
	public static $php_errors = array(
		E_ERROR             => 'Fatal Error',
		E_USER_ERROR        => 'User Error',
		E_PARSE             => 'Parse Error',
		E_WARNING           => 'Warning',
		E_USER_WARNING      => 'User Warning',
		E_STRICT            => 'Strict',
		E_NOTICE            => 'Notice',
		E_RECOVERABLE_ERROR => 'Recoverable Error',
	);

	/**
	 * response http code
	 * @var int
	 */
	public static $http_code = Rest_Response::HTTP_SERVER_ERROR;

	/**
	 * response http header
	 * @var array
	 */
	public static $header = array();

	/**
	 * error info
	 * @var array
	 */
	public static $error = array();
	
	public static $_error_config_name  = "errcode";

	/**
	 * Creates a new translated exception.
	 *
	 *     throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Something went terrible wrong, :user',
	 *         array(':user' => $user));
	 *
	 * @param   int    $http_code response http code
	 * @param   string $message error message
	 * @param   array  $variables translation variables
	 * @param   int|string $code  the exception code
	 */
	public function __construct($http_code = 500, $message, array $variables = NULL, $code = 0)
	{
		if (defined('E_DEPRECATED'))
		{
			// E_DEPRECATED only exists in PHP >= 5.3.0
			Rest_Exception::$php_errors[E_DEPRECATED] = 'Deprecated';
		}
		
		if(is_array($message))
		{
			$message = json_encode($message);
		}
		
		// Set the message
		$message = __($message, $variables);

		// Pass the message and integer code to the parent
		parent::__construct($message, (int) $code);

		// Save the unmodified code
		// @link http://bugs.php.net/39615
		$this->code = $code;

		self::$http_code = $http_code;
		self::$error['error'] = $message;
	}

	/**
	 * Magic object-to-string method.
	 *
	 *     echo $exception;
	 *
	 * @uses    Rest_Exception::text
	 * @return  string
	 */
	public function __toString()
	{
		return Rest_Exception::text($this);
	}

	/**
	 * Inline exception handler, displays the error message, source of the
	 * exception, and the stack trace of the error.
	 *
	 * @uses   Rest_Exception::text
	 * @param  Exception $e exception object
	 * @throws Rest_Exception
	 * @return boolean
	 */
	public static function handler(Exception $e)
	{
		try
		{			
			// Get the exception information
			$code = $e->getCode();
			
			if ($e instanceof ErrorException)
			{
				//some exceptions not have http code
				self::$http_code = Rest_Response::HTTP_SERVER_ERROR;
				self::$error['error'] = $e->getMessage();
			}

			//从其他异常抛出
			if(empty(self::$error['error'])) {
				self::$http_code = Rest_Response::HTTP_SERVER_ERROR;
				self::$error['error'] = $e->getMessage();
			}

			self::log($e);

			self::output($e);

		} catch (Exception $e)
		{
			self::output($e);
		}
	}

	/**
	 * write log
	 * @param Exception $e
	 */
	public static function log(Exception $e)
	{
		if (is_object(Rest::$log))
		{
			$base_log = Rest_Request::get_request_log();

			if (Rest_Request::$last_resource instanceof Rest_Resource)
			{
				$base_log = array_merge($base_log, Rest_Request::$last_resource->get_resource_log());
			}
			// Add this exception to the log
			Rest::$log->add(Rest_Log::ERROR, json_encode(self::$error), array(), $base_log);

			Rest::$log->add(Rest_Log::TRACE, Rest_Exception::text($e) . "\n--\n" . $e->getTraceAsString(), array(),
				$base_log);

			// Make sure the logs are written
			Rest::$log->write();
		}
	}

	/**
	 * http response
	 * @static
	 */
	public static function output(Exception $e)
	{
		// Clean the output buffer if one exists
		ob_get_level() and ob_clean();

		// Create a text version of the exception
		//开发环境和产品环境错误输出区分处理
		if (Rest::$environment !== Rest::PRODUCTION)
		{
			self::$error['trace'] = Rest_Exception::text($e) . "\n--\n" . $e->getTraceAsString();
		}
		else
		{
			//self::$error['error'] = Rest_Config::get('lang.server_error');
		}
		
		$traceinfo = $e->getTrace();
		$default_msg = array("code"=>0,"msg"=>$e->getMessage());
		if($traceinfo && $e->getMessage())
		{
			self::$error['error'] =  Rest_Config::get(self::$_error_config_name.".".$traceinfo[0]['class'].".".$traceinfo[0]['function'].".".$e->getMessage(),$default_msg);
		}
		
		if(!self::$error['error'])
		{
			self::$error['error'] = $default_msg;
		}

		//需要跳转，不需要返回消息体
		if (self::$http_code !== Rest_Response::HTTP_FOUND)
		{
			Rest_Response::instance()
				->set_status(self::$http_code)
				->headers(self::$header)
				->set_body(self::$error)
				->output();
		}
		else
		{
			Rest_Response::instance()
				->set_status(self::$http_code)
				->headers(self::$header)
				->output();
		}
		exit(1);
	}

	/**
	 * Get a single line of text representing the exception:
	 *
	 * Error [ Code ]: Message ~ File [ Line ]
	 *
	 * @param   Exception $e
	 * @return  string
	 */
	public static function text(Exception $e)
	{
		return sprintf('%s [ %s ]: %s ~ %s [ %d ]',
			get_class($e), $e->getCode(), strip_tags($e->getMessage()), Rest_Exception::path($e->getFile()),
			$e->getLine());
	}

	/**
	 * Removes application, system, resource path from a filename,
	 * replacing them with the plain text equivalents. Useful for debugging
	 * when you want to display a shorter path.
	 *
	 * @param   string $file path to debug
	 * @return  string
	 */
	public static function path($file)
	{
		if (strpos($file, APP_PATH) === 0)
		{
			$file = 'APP_PATH' . DS . substr($file, strlen(APP_PATH));
		}
		elseif (strpos($file, MOD_PATH) === 0)
		{
			$file = 'MOD_PATH' . DS . substr($file, strlen(MOD_PATH));
		}
		elseif (strpos($file, SYS_PATH) === 0)
		{
			$file = 'SYS_PATH' . DS . substr($file, strlen(SYS_PATH));
		}
		return $file;
	}

} // End Rest_Exception
