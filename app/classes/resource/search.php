<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * 不需要oauth2认证，继承Rest_Resource
 */
class Resource_Search extends Rest_Resource
{
	public function search()
	{
		$this->response->set_body(array('data' => 'search', 'method' => __FUNCTION__));
	}
}
