<?php defined('SYS_PATH') or die('No direct script access.');
/**
 *
 * 需要oauth2认证，继承Oauth2_Resource
 */
class Resource_Batch extends Oauth2_Resource {

	protected $_result = array();

	public function before($skip_verify = FALSE)
	{
		parent::before();
	}

	/**
	 * 批量操作只支持POST方法
	 * @method POST
	 * @throws Rest_Exception
	 */
	public function batch()
	{
		$data = $this->request->post();
		if (empty($data) OR ! is_array($data))
		{
			throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST,
				Rest_Config::get('lang.input_error'));
		}
		foreach ($data as $val)
		{
			if (! isset($val['id']) OR ! isset($val['method']) OR ! isset($val['to']))
			{
				throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST,
					Rest_Config::get('lang.input_data_incorrect'));
			}
		}
		$responses = array();
		foreach ($data as $key => $val)
		{
			$body = NULL;
			$val['body'] = isset($val['body']) ? $val['body'] : array();
			if (is_string($val['body']))
			{
				$val['body'] = $this->_get_reference($val['body']);
			}
			$val['body'] = (array) $val['body'];
			$hash = sha1($val['method'] . '~' . $val['to'] . '?' . http_build_query($val['body'], NULL, '&'));

			if (! isset($responses[$hash]))
			{
				try
				{
					$request = Rest_Request::instance($this->request->cache, $val['to'], $val['method'], $val['body']);
					$response = $request->exec();
					$responses[$hash] = array(
						'from' => $val['to'],
						'status' => $response->get_status(),
						'body' => $response->get_body()
					);
				} catch (Rest_Exception $e)
				{
					$responses[$hash] = array(
						'from' => $val['to'],
						'status' => Rest_Exception::$http_code,
						'body' => Rest_Exception::$error
					);
					Rest_Exception::log($e);
				} catch (Exception $e)
				{
					throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, $e->getMessage());
				}
			}
			$this->_result[$key] = array_merge(array('id' => $val['id']), $responses[$hash]);
		}
		$this->response->set_body($this->_result);
	}

	/**
	 * 获取引用结果的值
	 * @param string|array $val
	 * @return array|string
	 * @throws Rest_Exception
	 */
	private function _get_reference($val)
	{
		$data = '';
		if (is_array($val))
		{
			$data = array();
			foreach ($val as $k => $v)
			{
				$data[$k] = $this->_get_reference($v);
			}
		}
		else
		{
			if (preg_match("/\{(\d+(?:\.[^\.]+?)+)\}/", $val['body'], $matches))
			{
				$data = Rest_Arr::path($this->_result, $matches[1]);
				if (is_null($data))
				{
					throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST,
						Rest_Config::get('lang.input_error'));
				}
			}
		}
		return $data;
	}
}
