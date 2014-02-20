<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Log Write library
 *
 * @package    System
 * @category   Log
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
abstract class Rest_Log_Base {

	/**
	 * Numeric log level to string lookup table.
	 * @var array
	 */
	protected $_log_levels = array(
		LOG_EMERG => 'EMERGENCY',
		LOG_ALERT => 'ALERT',
		LOG_CRIT => 'CRITICAL',
		LOG_ERR => 'ERROR',
		LOG_WARNING => 'WARNING',
		LOG_NOTICE => 'NOTICE',
		LOG_INFO => 'INFO',
		LOG_DEBUG => 'DEBUG',
		8 => 'TRACE',
	);

	/**
	 * Writes each of the messages into the log file. The log file will be
	 * appended to the `YYYY/MM/DD.log.php` file, where YYYY is the current
	 * year, MM is the current month, and DD is the current day.
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array $messages
	 * @return  void
	 */
	public function write(array $messages)
	{
	}

	/**
	 * Allows the writer to have a unique key when stored.
	 *
	 *     echo $writer;
	 *
	 * @return  string
	 */
	final public function __toString()
	{
		return spl_object_hash($this);
	}

} // End Rest_Log_Write
