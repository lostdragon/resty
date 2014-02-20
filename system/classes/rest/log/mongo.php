<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Log Write library
 *
 * @package    System
 * @category   Log
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Log_Mongo extends Rest_Log_Base {

	/**
	 * @var Rest_Mongo
	 */
	protected $_mongo;

	/**
	 * @var string
	 */
	protected $_collection_name;

	/**
	 * Creates a new file logger. Checks that the directory exists and
	 * is writable.
	 *
	 *     $writer = new Rest_Log_Mongo($mongo);
	 *
	 * @param  string|Rest_Mongo $mongo
	 * @param  string $collection_name
	 * @throws Rest_Exception
	 */
	public function __construct($mongo, $collection_name = 'error_log')
	{
		if ($mongo instanceof Rest_Mongo)
		{
			$this->_mongo = $mongo;
		}
		else
		{
			$this->_mongo = Rest_Mongo::instance($mongo);
		}
		$this->_collection_name = $collection_name;
	}

	/**
	 * Writes each of the messages into the mongo.
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array $messages
	 * @return  void
	 */
	public function write(array $messages)
	{
		foreach ($messages as $message)
		{
			$message['level'] = $this->_log_levels[$message['level']];

			$this->_mongo->insert($this->_collection_name, $message);
			// Write each message into the log file
			// Format: time --- level: body
//			file_put_contents($filename,
//				PHP_EOL . $message['time'] . ' --- ' . $this->_log_levels[$message['level']] . ': ' . $message['body'],
//				FILE_APPEND);
		}
	}

} // End Rest_Log_Mongo
