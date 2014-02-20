<?php defined('SYS_PATH') or die('No direct script access.');

class Resource_Users_Relationships extends Oauth2_Resource {
	/**
	 * @var Model_Relationship
	 */
	protected $_model;

	public function before($skip_verify = FALSE)
	{
		parent::before();
//		$this->_model = Model_Relationship::instance();
	}

	public function get_list()
	{
		$this->response->set_body(array('method' => __METHOD__));
	}

	public function get()
	{
		$this->response->set_body(array('method' => __METHOD__));
	}

}
