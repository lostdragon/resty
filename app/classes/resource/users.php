<?php defined('SYS_PATH') or die('No direct script access.');
/**
 *
 * 需要oauth2认证，继承Resource
 */
class Resource_Users extends Oauth2_Resource {

	/**
	 * @var Model_Users
	 */
	protected $_model;

	public function before($skip_verify = FALSE)
	{
		parent::before();
		$this->_model = Model_Users::instance();
	}

	public function get_list()
	{
		$offset = (int) $this->request->query('offset', 0);
		$limit = (int) $this->request->query('limit', 10);
		if ($offset < 0 OR $limit < 1)
		{
			throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST, 'input offset or limit error');
		}
		$res = $this->_model->get_user_list($offset, $limit);
		$result = array(
			'data' => $res,
			'meta' => array(
				'total_count' => $this->_model->get_user_count(),
				'offset' => $offset,
				'limit' => $limit
			)
		);
		$this->response->set_body($result);
	}

	public function get()
	{
		$result = $this->_model->get_user($this->request->params('id'));
		if ($result)
		{
			$this->response->set_body($result);
		}
		else
		{
			throw new Rest_Exception(Rest_Response::HTTP_NO_FOUND, 'user not exist');
		}
	}

	/*
	public function post_list()
	{
		$username = $this->request->post('username', '');
		if (empty($username))
		{
			throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST, 'input username empty');
		}
		$user_id = $this->_model->add_user($username);
		if ($user_id > 0)
		{
			$this->response->set_body(
				array(
				     'user_id' => $user_id,
				     'username' => $username
				));
		}
		else
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'add user fail');
		}
	}

	public function put()
	{
		$id = $this->request->params('id');
		$username = $this->request->post('username', '');
		if (empty($username))
		{
			throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST, 'input username empty');
		}
		$result = $this->_model->get_user($id);
		if (! $result)
		{
			throw new Rest_Exception(Rest_Response::HTTP_NO_FOUND, 'user not exist');
		}
		$result = $this->_model->edit_user($id, $username);
		if ($result)
		{
			$this->response->set_body(
				array(
				     'user_id' => $id,
				     'username' => $username
				));
		}
		else
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'update user fail');
		}
	}

	public function delete()
	{
		$id = $this->request->params('id');
		$result = $this->_model->get_user($id);
		if (! $result)
		{
			throw new Rest_Exception(Rest_Response::HTTP_NO_FOUND, 'user not exist');
		}
		$result = $this->_model->delete_user($id);
		if (! $result)
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'delete user fail');
		}
	}
	*/
}
