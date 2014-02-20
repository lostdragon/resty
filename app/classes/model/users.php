<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Model_Relationship
 *
 * @package    app
 * @category   Models
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Model_Users extends Rest_Model {

	protected $_db;

	public function __construct()
	{
		$this->_db = Rest_Database::instance();
	}

	public function get_user_count()
	{
		return $this->_db->count_records('members');
	}

	public function get_user_list($offset, $limit)
	{
		$res = $this->_db->query_all_assoc("SELECT * FROM members LIMIT $offset, $limit");
		$result = array();
		foreach ($res as $val)
		{
			$result[] = $val;
		}
		return $result;
	}

	public function get_user($user_id)
	{
		return $this->_db->query_row_assoc('SELECT * FROM members WHERE uid = :user_id',
			array(':user_id' => $user_id));
	}

	/*
	public function add_user($username)
	{
		$this->_db->begin();
		$query = $this->_db->query('INSERT INTO test.rest_user (username) VALUES (:username)',
			array(':username' => $username));
		$this->_db->commit();
		$user_id = $query[0];

		return $user_id;
	}

	public function edit_user($user_id, $username)
	{
		$this->_db->begin();
		$query = $this->_db->query('UPDATE test.rest_user SET username = :username WHERE user_id = :user_id',
			array(':username' => $username, ':user_id' => $user_id));
		if ($query)
		{
			$this->_db->commit();
		}
		return TRUE;
	}

	public function delete_user($user_id)
	{
		$this->_db->query('DELETE FROM test.rest_user WHERE user_id = :user_id', array(':user_id' => $user_id));
		return TRUE;
	}
	*/
}
