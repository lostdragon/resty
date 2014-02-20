<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Log library
 *
 * @package    System
 * @category   Log
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Log {

	// Log message levels - Windows users see PHP Bug #18090
	const EMERGENCY = LOG_EMERG; // 0
	const ALERT = LOG_ALERT; // 1
	const CRITICAL = LOG_CRIT; // 2
	const ERROR = LOG_ERR; // 3
	const WARNING = LOG_WARNING; // 4
	const NOTICE = LOG_NOTICE; // 5
	const INFO = LOG_INFO; // 6
	const DEBUG = LOG_DEBUG; // 7
	const TRACE = 8;

	/**
	 * @var  string  timestamp format for log entries
	 */
	public static $timestamp = 'Y-m-d H:i:s';

	/**
	 * @var  string  timezone for log entries
	 */
	public static $timezone;

	/**
	 * @var  boolean  immediately write when logs are added
	 */
	public static $write_on_add = FALSE;

	/**
	 * @var  Rest_Log  Singleton instance container
	 */
	protected static $_instance;

	/**
	 * Get the singleton instance of this class and enable writing at shutdown.
	 *
	 *     $log = Rest_Log::instance();
	 *
	 * @return  Rest_Log
	 */
	public static function instance()
	{
		if (Rest_Log::$_instance === NULL)
		{
			// Create a new instance
			Rest_Log::$_instance = new Rest_Log;

			// Write the logs at shutdown
			register_shutdown_function(array(Rest_Log::$_instance, 'write'));
		}

		return Rest_Log::$_instance;
	}

	/**
	 * @var  array  list of added messages
	 */
	protected $_messages = array();

	/**
	 * @var  array  list of log writers
	 */
	protected $_writers = array();

	/**
	 * Attaches a log writer, and optionally limits the levels of messages that
	 * will be written by the writer.
	 *
	 *     $log->attach($writer);
	 *
	 * @param   Rest_Log_Base $writer    Rest_Log_File instance
	 * @param   array      $levels    array of messages levels to write OR max level to write
	 * @param   integer    $min_level min level to write IF $levels is not an array
	 * @return  Rest_Log
	 */
	public function attach(Rest_Log_Base $writer, $levels = array(), $min_level = 0)
	{
		if (! is_array($levels))
		{
			$levels = range($min_level, $levels);
		}

		$this->_writers["{$writer}"] = array
		(
			'object' => $writer,
			'levels' => $levels
		);

		return $this;
	}

	/**
	 * Detaches a log writer. The same writer object must be used.
	 *
	 *     $log->detach($writer);
	 *
	 * @param   Rest_Log_Base $writer Rest_Log_File instance
	 * @return  Rest_Log
	 */
	public function detach(Rest_Log_Base $writer)
	{
		// Remove the writer
		unset($this->_writers["{$writer}"]);

		return $this;
	}

	/**
	 * Adds a message to the log. Replacement values must be passed in to be
	 * replaced using [strtr](http://php.net/strtr).
	 *
	 *     $log->add(Rest_Log::ERROR, 'Could not locate user: :user', array(
	 *         ':user' => $username,
	 *     ));
	 *
	 * @param   string $level  level of message
	 * @param   string $message message body
	 * @param   array  $values values to replace in the message
	 * @param   array  $base_log append to log
	 * @return  Rest_Log
	 */
	public function add($level, $message, array $values = NULL, $base_log = array())
	{
		if ($values)
		{
			// Insert the values into the message
			$message = strtr($message, $values);
		}

		// Create a new message and timestamp it
		$this->_messages[] = array_merge(
			array(
			     'time'      => self::formatted_time('now', self::$timestamp, self::$timezone),
			     'exec_time' => microtime(TRUE) - Rest_Config::get('_request_time', 0),
			     'level'     => $level,
			     'code'      => Rest_Exception::$http_code,
			     'server_ip' => Rest_Helper_Input::get_server_ip(),
			     'client_ip' => Rest_Helper_Input::get_client_ip(),
			     'body'      => $message,
			), $base_log);

		if (self::$write_on_add)
		{
			// Write logs as they are added
			$this->write();
		}

		return $this;
	}

	/**
	 * Write and clear all of the messages.
	 *
	 *     $log->write();
	 *
	 * @return  void
	 */
	public function write()
	{
		if (empty($this->_messages))
		{
			// There is nothing to write, move along
			return;
		}

		// Import all messages locally
		$messages = $this->_messages;

		// Reset the messages array
		$this->_messages = array();

		foreach ($this->_writers as $writer)
		{
			if (empty($writer['levels']))
			{
				// Write all of the messages
				$writer['object']->write($messages);
			}
			else
			{
				// Filtered messages
				$filtered = array();

				foreach ($messages as $message)
				{
					if (in_array($message['level'], $writer['levels']))
					{
						// Writer accepts this kind of message
						$filtered[] = $message;
					}
				}

				// Write the filtered messages
				$writer['object']->write($filtered);
			}
		}
	}

	/**
	 * Returns a date/time string with the specified timestamp format
	 *
	 *     $time = Date::formatted_time('5 minutes ago');
	 *
	 * @see     http://php.net/manual/en/datetime.construct.php
	 * @param   string  $datetime_str     datetime string
	 * @param   string  $timestamp_format timestamp format
	 * @param   string  $timezone         timezone
	 * @return  string
	 */
	public static function formatted_time($datetime_str = 'now', $timestamp_format = NULL, $timezone = NULL)
	{
		$timestamp_format = ($timestamp_format == NULL) ? self::$timestamp : $timestamp_format;
		$timezone = ($timezone === NULL) ? self::$timezone : $timezone;

		$time = new DateTime($datetime_str, new DateTimeZone(
			$timezone ? $timezone : date_default_timezone_get()
		));

		return $time->format($timestamp_format);
	}


} // End Rest_Log
