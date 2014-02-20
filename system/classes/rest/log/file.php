<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Log Write library
 *
 * @package    System
 * @category   Log
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Log_File extends Rest_Log_Base {

	/**
	 * @var  string  Directory to place log files in
	 */
	protected $_directory;

	/**
	 * Creates a new file logger. Checks that the directory exists and
	 * is writable.
	 *
	 *     $writer = new Rest_Log_File($directory);
	 *
	 * @param   string $directory log directory
	 * @throws Rest_Exception
	 */
	public function __construct($directory)
	{
		if (! is_dir($directory) OR ! is_writable($directory))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Directory :dir must be writable',
				array(':dir' => Rest_Exception::path($directory)));
		}

		// Determine the directory path
		$this->_directory = realpath($directory) . DIRECTORY_SEPARATOR;
	}

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
		// Set the yearly directory name
		$directory = $this->_directory . date('Y');

		if (! is_dir($directory))
		{
			// Create the yearly directory
			mkdir($directory, 02777);

			// Set permissions (must be manually set to fix umask issues)
			chmod($directory, 02777);
		}

		// Add the month to the directory
		$directory .= DIRECTORY_SEPARATOR . date('m');

		if (! is_dir($directory))
		{
			// Create the monthly directory
			mkdir($directory, 02777);

			// Set permissions (must be manually set to fix umask issues)
			chmod($directory, 02777);
		}

		// Set the name of the log file
		$filename = $directory . DIRECTORY_SEPARATOR . date('d') . '.php';

		if (! file_exists($filename))
		{
			// Create the log file
			file_put_contents($filename, Rest::FILE_SECURITY . ' ?>' . PHP_EOL);

			// Allow anyone to write to log files
			chmod($filename, 0666);
		}

		foreach ($messages as $message)
		{
			// Write each message into the log file
			// Format: time --- level: body
			file_put_contents($filename,
				PHP_EOL . $message['time'] . ' --- ' . $this->_log_levels[$message['level']] . ': ' . $message['body'],
				FILE_APPEND);
		}
	}

} // End Rest_Log_File
