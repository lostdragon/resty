<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Rest_Resource library
 *
 * @package    Rest
 * @category   Rest_Resource
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Resource_Exception extends Rest_Exception {
}

class Rest_Resource {

	/**
	 * @var Rest_Request
	 */
	public $request;

	/**
	 * @var Rest_Response
	 */
	public $response;

	/**
	 * 赋值请求和响应
	 * @param Rest_Request $request
	 * @param Rest_Response $response
	 */
	public function __construct(Rest_Request $request, Rest_Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	/**
	 * 执行请求方法前操作
	 */
	public function before()
	{
	}

	/**
	 * 执行请求方法后操作
	 */
	public function after()
	{
	}

	/**
	 * 判断方式是否被支持
	 * @param string $method
	 * @throws Rest_Exception
	 */
	public function check_method($method)
	{
		if($this->request->get_request_method() != $method)
		{
			throw new Rest_Exception(Rest_Response::HTTP_METHOD_NOT_ALLOWED, 'method not support');
		}
	}
	
	/**
	 * 获取资源日志
	 * @return array
	 */
	public static function get_resource_log()
	{
		return array();
	}
}
