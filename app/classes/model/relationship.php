<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Model_Relationship
 *
 * @package    app
 * @category   Models
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Model_Relationship extends Rest_Model {

	protected $_mq;
	protected $_mg;
	protected $_db;
	protected $_mc;

	public function __construct()
	{
		$this->_db = Rest_Database::instance();
//		$this->_mq = Rest_Rabbit::instance();
		$this->_mg = Rest_Mongo::instance();
		$this->_mc = Rest_Cache::instance();
	}

	public function mc_test()
	{
//		$this->_mc->set('test', 'aaa');
		return $this->_mc->get('test');

	}
	public function db_test()
	{
//		$this->_db->begin();
//		$query = $this->_db->query("update clients set client_secret = 'ccc' WHERE client_id = 123");
//		if($query) $this->_db->commit();

		return $this->_db->query_row_assoc('SELECT * FROM agency WHERE id = :id', array(':id' => 4));
	}

	public function mq_test()
	{
		return $this->_mq->publish(array('test' => '123'), '#.12138610.#', 'momo_nd');
	}

	public function mg_test()
	{
		return $this->_mg->find_one('counter', array('_id' => 'main'));
	}

}
