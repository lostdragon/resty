<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * 不需要oauth2认证，继承Rest_Resource
 */
class Resource_Welcome extends Rest_Resource
{
	public function get()
	{
		$this->response->set_body(array('data' => 'welcome', 'method' => __FUNCTION__));
	}
}
