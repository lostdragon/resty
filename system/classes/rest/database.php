<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * MySQLi database connection.
 *
 * @package    System
 * @category   Database
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Database {

	// Rest_Database in use by each connection
	protected static $_current_databases = array();

	// Identifier for this connection within the PHP driver
	protected $_connection_id;

	// MySQL uses a backtick for identifiers
	protected $_identifier = '`';

	/**
	 * @var  string  default instance name
	 */
	public static $default = 'default';

	/**
	 * @var  array  Rest_Database instances
	 */
	public static $instances = array();

	/**
	 * @var  string  the last query executed
	 */
	public $last_query;

	// Instance name
	protected $_instance;

	/**
	 * Raw server connection
	 * @var mysqli[]|mysqli_sql_exception[]
	 */
	protected $_connection = array();

	/**
	 * weather is master
	 * @var bool
	 */
	protected $_is_slave = FALSE;

	/**
	 * weather transaction in progress
	 * @var bool
	 */
	protected $_transaction = FALSE;

	// Identifier for this connection within the PHP driver
	protected $_server_id = 0;

	/**
	 * Configuration array
	 * @var array
	 */
	protected $_config;

	/**
	 * Get a singleton Rest_Database instance. If configuration is not specified,
	 * it will be loaded from the database configuration file using the same
	 * group as the name.
	 *
	 *     // Load the default database
	 *     $db = Rest_Database::instance();
	 *
	 *     // Create a custom configured instance
	 *     $db = Rest_Database::instance('custom', $config);
	 * @static
	 * @param   string $name   instance name
	 * @param   array  $config   configuration parameters
	 * @throws  Rest_Exception
	 * @return  Rest_Database
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = Rest_Database::$default;
		}

		if (! isset(Rest_Database::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this database
				$config = Rest_Config::get('database.' . $name);
			}

			if (empty($config))
			{
				throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Database not defined in :name configuration',
					array(':name' => $name));
			}

			// Create the database connection instance
			Rest_Database::$instances[$name] = new Rest_Database($name, $config);
		}

		return Rest_Database::$instances[$name];
	}

	/**
	 * Stores the database configuration locally and name the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [Rest_Database::instance].
	 *
	 * @param string $name
	 * @param array $config
	 */
	protected function __construct($name, array $config)
	{
		// Set the instance name
		$this->_instance = $name;

		// Store the config locally
		$this->_config = $config;
	}

	/**
	 * Disconnect from the database when the object is destroyed.
	 *
	 *     // Destroy the database instance
	 *     unset(Rest_Database::instances[(string) $db], $db);
	 *
	 * [!!] Calling `unset($db)` is not enough to destroy the database, as it
	 * will still be stored in `Rest_Database::$instances`.
	 *
	 * @return  void
	 */
	final public function __destruct()
	{
		if ($this->_transaction)
		{
			$this->rollback();
		}
		$this->disconnect();
	}

	/**
	 * Returns the database instance name.
	 *
	 *     echo (string) $db;
	 *
	 * @return  string
	 */
	final public function __toString()
	{
		return $this->_instance;
	}

	/**
	 * Connect to the database. This is called automatically when the first
	 * query is executed.
	 *
	 *     $db->connect();
	 *
	 * @throws  Rest_Exception
	 * @return  void
	 */
	public function connect()
	{
		if (isset($this->_connection[$this->_is_slave]))
		{
			return;
		}

		// Extract the connection parameters, adding required variables
		$persistent = FALSE;
		$port = 3306;
		$hostname = $username = $password = $database = '';
		$socket = '';

		if (! $this->_is_slave)
		{
			$servers = $this->_config['master'];
		}
		else
		{
			$servers = isset($this->_config['slave']) ? $this->_config['slave'] : $this->_config['master'];
			$this->_is_slave = isset($this->_config['slave']) ? FALSE : TRUE;
		}
		$connection = $this->_is_slave ? 'slave' : 'master';

		// Ensure reporting is set to exception
		mysqli_report(MYSQLI_REPORT_STRICT);

		while (! isset($this->_connection[$this->_is_slave]) && count($servers))
		{
			$key = array_rand($servers);

			extract($servers[$key]);

			// Prevent this information from showing up in traces
			unset($this->_config[$connection][$key]['username'], $this->_config[$connection][$key]['password']);
			try
			{
				if ($persistent)
				{
					// Create a persistent connection
					$this->_connection[$this->_is_slave] = new mysqli(
						'p:' . $hostname, $username, $password, $database, $port, $socket);
				}
				else
				{
					// Create a connection and force it to be a new link
					$this->_connection[$this->_is_slave] =
						new mysqli($hostname, $username, $password, $database, $port, $socket);
				}
			}
			catch (mysqli_sql_exception $e)
			{
				// Set exception to connection for error report
				$this->_connection[$this->_is_slave] = $e;
			}

			if (! isset($this->_connection[$this->_is_slave]) OR $this->_connection[$this->_is_slave] instanceof
				mysqli_sql_exception OR $this->_connection[$this->_is_slave]->connect_error
			)
			{
				// We couldn't connect.  Remove this server:
				unset($servers[$key]);
			}
			else
			{
				$this->_server_id = $key;
				break;
			}
		}

		// No connection exists
		if (! isset($this->_connection[$this->_is_slave]) OR $this->_connection[$this->_is_slave] instanceof
			mysqli_sql_exception OR $this->_connection[$this->_is_slave]->connect_error
		)
		{
			if (isset($this->_connection[$this->_is_slave]))
			{
				if ($this->_connection[$this->_is_slave] instanceof mysqli_sql_exception)
				{
					$error = $this->_connection[$this->_is_slave]->getMessage() .
						'(' . $this->_connection[$this->_is_slave]->getCode()
						. ')';
				}
				else
				{
					$error = $this->_connection[$this->_is_slave]->connect_error .
						'(' . $this->_connection[$this->_is_slave]->connect_errno
						. ')';
				}
			}
			else
			{
				$error = 'no server to connect';
			}

			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR,
				'Could not connect to :connection database, :error',
				array(':connection' => $connection, ':error' => $error)
			);
		}
		// \xFF is a better delimiter, but the PHP driver uses underscore
		$this->_connection_id = sha1($hostname . '_' . $username . '_' . $password);
		Rest_Database::$_current_databases[$this->_connection_id] = $database;

		if (! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}

		if (! empty($this->_config[$connection][$this->_server_id]['variables']))
		{
			// Set session variables
			$variables = array();

			foreach ($this->_config[$connection][$this->_server_id]['variables'] as $var => $val)
			{
				$variables[] = 'SESSION ' . $var . ' = ' . $this->quote($val);
			}

			$this->_connection[$this->_is_slave]->query('SET ' . implode(', ', $variables));
		}
	}

	/**
	 * Quote a value for an SQL query.
	 *
	 *     $db->quote(NULL);   // 'NULL'
	 *     $db->quote(10);     // 10
	 *     $db->quote('fred'); // 'fred'
	 *
	 * Objects passed to this function will be converted to strings.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed $value any value to quote
	 * @return  string
	 * @uses    Rest_Database::escape
	 */
	public function quote($value)
	{
		if ($value === NULL)
		{
			return 'NULL';
		}
		elseif ($value === TRUE)
		{
			return "'1'";
		}
		elseif ($value === FALSE)
		{
			return "'0'";
		}
		elseif (is_object($value))
		{
			// Convert the object to a string
			return $this->quote((string) $value);
		}
		elseif (is_array($value))
		{
			return '(' . implode(', ', array_map(array($this, __FUNCTION__), $value)) . ')';
		}
		elseif (is_int($value))
		{
			return (int) $value;
		}
		elseif (is_float($value))
		{
			// Convert to non-locale aware float to prevent possible commas
			return (string)$value;
		}

		return $this->escape($value);
	}

	/**
	 * Disconnect from the database. This is called automatically by [Rest_Database::__destruct].
	 * Clears the database instance from [Rest_Database::$instances].
	 *
	 *     $db->disconnect();
	 *
	 * @return  boolean
	 */
	public function disconnect()
	{
		try
		{
			// Rest_Database is assumed disconnected
			$status = TRUE;
			foreach ($this->_connection as $connection)
			{
				if (is_resource($connection))
				{
					/**
					 * @var mysqli $connection
					 */
					$status = $connection->close();
				}
			}
			// Clear the connection
			$this->_connection = array();

			// Clear the instance
			unset(Rest_Database::$instances[$this->_instance]);
		} catch (Exception $e)
		{
			// Rest_Database is probably not disconnected
			$status = empty($this->_connection);
		}

		return $status;
	}

	/**
	 * Set the connection character set. This is called automatically by [Rest_Database::connect].
	 *
	 *     $db->set_charset('utf8');
	 *
	 * @param   string $charset character set name
	 * @throws  Rest_Exception
	 */
	public function set_charset($charset)
	{
		// Make sure the database is connected
		isset($this->_connection[$this->_is_slave]) or $this->connect();

		$status = $this->_connection[$this->_is_slave]->set_charset($charset);

		if ($status === FALSE)
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, ':error',
				array(':error' => $this->_connection[$this->_is_slave]->connect_error), $this->_connection[$this->_is_slave]->errno);
		}
	}

	/**
	 * Perform an SQL query of the given type.
	 *
	 *     // Make a SELECT query and use mysqli_result for results
	 *     $db->query('SELECT * FROM groups');
	 *
	 *     // Make a SELECT query and use mysqli_result for results
	 *     $db->query('SELECT * FROM groups WHERE uid = :uid', array(':uid' => 123));
	 *
	 *
	 * @param   string $sql SQL query
	 * @param   array $params SQL params
	 * @param   bool  $is_slave
	 * @return  mysqli_result Database_Result for SELECT queries
	 * @return  array    list (insert id, row count) for INSERT queries
	 * @return  int  number of affected rows for all other queries
	 * @throws Rest_Exception
	 */
	public function query($sql, $params = array(), $is_slave = FALSE)
	{
		$sql = trim($sql);
		$type = strtoupper(substr($sql, 0, strpos($sql, ' ')));

		$this->_is_slave = $is_slave;

		if (!in_array($type, array('SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN')))
		{
			$this->_is_slave = FALSE;
		}

		// Make sure the database is connected
		isset($this->_connection[$this->_is_slave]) or $this->connect();
		$connection = $this->_is_slave ? 'slave' : 'master';
		if (! empty($this->_config[$connection][$this->_server_id]['persistent']) AND
			$this->_config[$connection][$this->_server_id]['database'] !==
			Rest_Database::$_current_databases[$this->_connection_id]
		)
		{
			// Select database on persistent connections
			$this->_connection[$this->_is_slave]->select_db($this->_config[$connection][$this->_server_id]['database']);
			Rest_Database::$_current_databases[$this->_connection_id] = $this->_config[$connection][$this->_server_id]['database'];
		}

		if ($params)
		{
			foreach ($params as & $val)
			{
				$val = $this->quote($val);
			}
			$sql = __($sql, $params);
		}

		// Execute the query		



		if (($result = $this->_connection[$this->_is_slave]->query($sql)) === FALSE)
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, ':error [ :query ]',
				array(':error' => $this->_connection[$this->_is_slave]->error, ':query' => $sql),
				$this->_connection[$this->_is_slave]->errno);
		}

		// Set the last query
		$this->last_query = $sql;

		if (in_array($type, array('SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN')))
		{
			// Return an iterator of results
			return new Rest_Database_Result($result);
		}
		else
		{
			if ($type === 'INSERT')
			{
				// Return a list of insert id and rows created
				return array(
					$this->_connection[$this->_is_slave]->insert_id,
					$this->_connection[$this->_is_slave]->affected_rows,
				);
			}
			else
			{
				// Return the number of rows affected
				return $this->_connection[$this->_is_slave]->affected_rows;
			}
		}
	}

	/**
	 * Perform a query, return all the results
	 *
	 *     // Make a SELECT query and use mysqli_result for results
	 *     $db->query_all_assoc('SELECT * FROM groups');
	 *
	 * @param   string $sql SQL query
	 * @param   array $params SQL params
	 * @param   bool  $is_slave
	 * @return  array
	 * @throws Rest_Exception
	 */
	public function query_all_assoc($sql, $params = array(), $is_slave = FALSE)
	{
		$res = $this->query($sql, $params, $is_slave);
		$rs = array();
		while ($row = $res->fetch_assoc())
		{
			$rs[] = $row;
		}

		$res->free();
		return $rs;
	}

	/**
	 * Perform a query, return the first row
	 *
	 *     // Make a SELECT query
	 *     $db->query_row_assoc('SELECT * FROM groups WHERE uid = :uid', array(':uid' => 123));
	 *
	 *
	 * @param   string $sql SQL query
	 * @param   array $params SQL params
	 * @param   bool  $is_slave
	 * @return  array|bool
	 * @throws Rest_Exception
	 */
	public function query_row_assoc($sql, $params = array(), $is_slave = FALSE)
	{
		$res = $this->query($sql, $params, $is_slave);
		if ($row = $res->fetch_assoc())
		{
			$rs = $row;
		}
		else
		{
			$rs = false;
		}
		$res->free();
		return $rs;
	}

	/**
	 * Perform a query, return the first row
	 *
	 *     // Make a SELECT query
	 *     $db->query_row('SELECT * FROM groups WHERE uid = :uid', array(':uid' => 123));
	 *
	 *
	 * @param   string $sql SQL query
	 * @param   array $params SQL params
	 * @param   bool  $is_slave
	 * @return  array|bool
	 * @throws Rest_Exception
	 */
	public function query_row($sql, $params = array(), $is_slave = FALSE)
	{
		$res = $this->query($sql, $params, $is_slave);
		if ($row = $res->fetch_array())
		{
			$rs = $row;
		}
		else
		{
			$rs = false;
		}
		$res->free();
		return $rs;
	}

	/**
	 * Perform a query, return the first column of the first row
	 *
	 *     // Make a SELECT query
	 *     $db->query_one('SELECT id FROM groups WHERE uid = :uid', array(':uid' => 123));
	 *
	 *
	 * @param   string $sql SQL query
	 * @param   array $params SQL params
	 * @param   bool  $is_slave
	 * @return  mixed
	 * @throws Rest_Exception
	 */
	public function query_one($sql, $params = array(), $is_slave = FALSE)
	{
		$res = $this->query($sql, $params, $is_slave);
		if ($row = $res->fetch_assoc())
		{
			$val = array_pop($row);
		}
		else
		{
			$val = false;
		}
		$res->free();
		return $val;
	}


	/**
	 * Count the number of records in a table.
	 *
	 *     // Get the total number of records in the "users" table
	 *     $count = $db->count_records('users');
	 *
	 * @param   mixed $table table name string or array(query, alias)
	 * @return  int
	 */
	public function count_records($table)
	{
		// Quote the table name
		$table = $this->quote_table($table);

		return (int) $this->query_one('SELECT COUNT(*) AS total_row_count FROM ' . $table);
	}

	/**
	 * Returns a normalized array describing the SQL data type
	 *
	 *     $db->datatype('char');
	 *
	 * @param   string $type SQL data type
	 * @return  array
	 */
	public function datatype($type)
	{
		static $types = array
		(
			// SQL-92
			'bit' => array('type' => 'string', 'exact' => TRUE),
			'bit varying' => array('type' => 'string'),
			'char' => array('type' => 'string', 'exact' => TRUE),
			'char varying' => array('type' => 'string'),
			'character' => array('type' => 'string', 'exact' => TRUE),
			'character varying' => array('type' => 'string'),
			'date' => array('type' => 'string'),
			'dec' => array('type' => 'float', 'exact' => TRUE),
			'decimal' => array('type' => 'float', 'exact' => TRUE),
			'double precision' => array('type' => 'float'),
			'float' => array('type' => 'float'),
			'int' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
			'integer' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
			'interval' => array('type' => 'string'),
			'national char' => array('type' => 'string', 'exact' => TRUE),
			'national char varying' => array('type' => 'string'),
			'national character' => array('type' => 'string', 'exact' => TRUE),
			'national character varying' => array('type' => 'string'),
			'nchar' => array('type' => 'string', 'exact' => TRUE),
			'nchar varying' => array('type' => 'string'),
			'numeric' => array('type' => 'float', 'exact' => TRUE),
			'real' => array('type' => 'float'),
			'smallint' => array('type' => 'int', 'min' => '-32768', 'max' => '32767'),
			'time' => array('type' => 'string'),
			'time with time zone' => array('type' => 'string'),
			'timestamp' => array('type' => 'string'),
			'timestamp with time zone' => array('type' => 'string'),
			'varchar' => array('type' => 'string'),
			// SQL:1999
			'binary large object' => array('type' => 'string', 'binary' => TRUE),
			//			'blob'                              => array('type' => 'string', 'binary' => TRUE),
			'boolean' => array('type' => 'bool'),
			'char large object' => array('type' => 'string'),
			'character large object' => array('type' => 'string'),
			'clob' => array('type' => 'string'),
			'national character large object' => array('type' => 'string'),
			'nchar large object' => array('type' => 'string'),
			'nclob' => array('type' => 'string'),
			'time without time zone' => array('type' => 'string'),
			'timestamp without time zone' => array('type' => 'string'),
			// SQL:2003
			'bigint' => array('type' => 'int', 'min' => '-9223372036854775808', 'max' => '9223372036854775807'),
			// SQL:2008
			'binary' => array('type' => 'string', 'binary' => TRUE, 'exact' => TRUE),
			'binary varying' => array('type' => 'string', 'binary' => TRUE),
			'varbinary' => array('type' => 'string', 'binary' => TRUE),
			//Mysql
			'blob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '65535'),
			'bool' => array('type' => 'bool'),
			'bigint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '18446744073709551615'),
			'datetime' => array('type' => 'string'),
			'decimal unsigned' => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
			'double' => array('type' => 'float'),
			'double precision unsigned' => array('type' => 'float', 'min' => '0'),
			'double unsigned' => array('type' => 'float', 'min' => '0'),
			'enum' => array('type' => 'string'),
			'fixed' => array('type' => 'float', 'exact' => TRUE),
			'fixed unsigned' => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
			'float unsigned' => array('type' => 'float', 'min' => '0'),
			'int unsigned' => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
			'integer unsigned' => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
			'longblob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '4294967295'),
			'longtext' => array('type' => 'string', 'character_maximum_length' => '4294967295'),
			'mediumblob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '16777215'),
			'mediumint' => array('type' => 'int', 'min' => '-8388608', 'max' => '8388607'),
			'mediumint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '16777215'),
			'mediumtext' => array('type' => 'string', 'character_maximum_length' => '16777215'),
			'national varchar' => array('type' => 'string'),
			'numeric unsigned' => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
			'nvarchar' => array('type' => 'string'),
			'point' => array('type' => 'string', 'binary' => TRUE),
			'real unsigned' => array('type' => 'float', 'min' => '0'),
			'set' => array('type' => 'string'),
			'smallint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '65535'),
			'text' => array('type' => 'string', 'character_maximum_length' => '65535'),
			'tinyblob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '255'),
			'tinyint' => array('type' => 'int', 'min' => '-128', 'max' => '127'),
			'tinyint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '255'),
			'tinytext' => array('type' => 'string', 'character_maximum_length' => '255'),
			'year' => array('type' => 'string'),
		);

		$type = str_replace(' zerofill', '', $type);

		if (isset($types[$type]))
		{
			return $types[$type];
		}

		return array();
	}

	/**
	 * Set is autocommit
	 * @param bool $mode is auto commit
	 * @return bool
	 */
	public function autocommit($mode = TRUE)
	{
		$this->_is_slave = FALSE;
		// Make sure the database is connected
		isset($this->_connection[$this->_is_slave]) or $this->connect();

		return $this->_connection[$this->_is_slave]->autocommit($mode);

	}

	/**
	 * Start a SQL transaction
	 *
	 * @return boolean
	 */
	public function begin()
	{
		$this->_is_slave = FALSE;
		// Make sure the database is connected
		isset($this->_connection[$this->_is_slave]) or $this->connect();

		$result = $this->_connection[$this->_is_slave]->autocommit(FALSE);
		$this->_transaction = TRUE;
		return $result;
	}

	/**
	 * Commit a SQL transaction
	 *
	 * @return boolean
	 */
	public function commit()
	{
		$this->_is_slave = FALSE;
		// Make sure the database is connected
		isset($this->_connection[$this->_is_slave]) or $this->connect();
		if ($this->_transaction)
		{
			$result = $this->_connection[$this->_is_slave]->commit();
			$this->_connection[$this->_is_slave]->autocommit(TRUE);
			$this->_transaction = FALSE;
		}
		else
		{
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * Rollback a SQL transaction
	 *
	 * @return boolean
	 */
	public function rollback()
	{
		$this->_is_slave = FALSE;
		// Make sure the database is connected
		isset($this->_connection[$this->_is_slave]) or $this->connect();
		if ($this->_transaction)
		{
			$result = $this->_connection[$this->_is_slave]->rollback();
			$this->_connection[$this->_is_slave]->autocommit(TRUE);
			$this->_transaction = FALSE;
		} else {
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * @param null $like
	 * @return array
	 */
	public function list_tables($like = NULL)
	{
		if (is_string($like))
		{
			// Search for table names
			$res = $this->query('SHOW TABLES LIKE ' . $this->quote($like));
		}
		else
		{
			// Find all table names
			$res = $this->query('SHOW TABLES');
		}

		$tables = array();

		foreach ($res->fetch_all() as $row)
		{
			$tables[] = reset($row);
		}
		$res->free();
		return $tables;
	}

	/**
	 * Return the table prefix defined in the current configuration.
	 *
	 *     $prefix = $db->table_prefix();
	 *
	 * @return  string
	 */
	public function table_prefix()
	{
		return $this->_config['table_prefix'];
	}

	/**
	 * Quote a database column name and add the table prefix if needed.
	 *
	 *     $column = $db->quote_column($column);
	 *
	 * You can also use SQL methods within identifiers.
	 *
	 *     // The value of "column" will be quoted
	 *     $column = $db->quote_column('COUNT("column")');
	 *
	 * Objects passed to this function will be converted to strings.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed $column  column name or array(column, alias)
	 * @return  string
	 * @uses    Rest_Database::quote_identifier
	 * @uses    Rest_Database::table_prefix
	 */
	public function quote_column($column)
	{
		if (is_array($column))
		{
			list($column, $alias) = $column;
		}

		// Convert to a string
		$column = (string) $column;

		if ($column === '*')
		{
			return $column;
		}
		elseif (strpos($column, '"') !== FALSE)
		{
			// Quote the column in FUNC("column") identifiers
			$column = preg_replace('/"(.+?)"/e', '$this->quote_column("$1")', $column);
		}
		elseif (strpos($column, '.') !== FALSE)
		{
			$parts = explode('.', $column);

			if ($prefix = $this->table_prefix())
			{
				// Get the offset of the table name, 2nd-to-last part
				$offset = count($parts) - 2;

				// Add the table prefix to the table name
				$parts[$offset] = $prefix . $parts[$offset];
			}

			foreach ($parts as & $part)
			{
				if ($part !== '*')
				{
					// Quote each of the parts
					$part = $this->_identifier . $part . $this->_identifier;
				}
			}

			$column = implode('.', $parts);
		}
		else
		{
			$column = $this->_identifier . $column . $this->_identifier;
		}


		if (isset($alias))
		{
			$column .= ' AS ' . $this->_identifier . $alias . $this->_identifier;
		}

		return $column;
	}

	/**
	 * Quote a database table name and adds the table prefix if needed.
	 *
	 *     $table = $db->quote_table($table);
	 *
	 * Objects passed to this function will be converted to strings.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed $table table name or array(table, alias)
	 * @return  string
	 * @uses    Rest_Database::quote_identifier
	 * @uses    Rest_Database::table_prefix
	 */
	public function quote_table($table)
	{
		if (is_array($table))
		{
			list($table, $alias) = $table;
		}

		// Convert to a string
		$table = (string) $table;

		if (strpos($table, '.') !== FALSE)
		{
			$parts = explode('.', $table);

			if ($prefix = $this->table_prefix())
			{
				// Get the offset of the table name, last part
				$offset = count($parts) - 1;

				// Add the table prefix to the table name
				$parts[$offset] = $prefix . $parts[$offset];
			}

			foreach ($parts as & $part)
			{
				// Quote each of the parts
				$part = $this->_identifier . $part . $this->_identifier;
			}

			$table = implode('.', $parts);
		}
		else
		{
			// Add the table prefix
			$table = $this->_identifier . $this->table_prefix() . $table . $this->_identifier;
		}

		if (isset($alias))
		{
			// Attach table prefix to alias
			$table .= ' AS ' . $this->_identifier . $this->table_prefix() . $alias . $this->_identifier;
		}

		return $table;
	}

	/**
	 * Quote a database identifier
	 *
	 * Objects passed to this function will be converted to strings.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed $value  any identifier
	 * @return  string
	 */
	public function quote_identifier($value)
	{
		if (is_array($value))
		{
			list($value, $alias) = $value;
		}

		// Convert to a string
		$value = (string) $value;

		if (strpos($value, '.') !== FALSE)
		{
			$parts = explode('.', $value);

			foreach ($parts as & $part)
			{
				// Quote each of the parts
				$part = $this->_identifier . $part . $this->_identifier;
			}

			$value = implode('.', $parts);
		}
		else
		{
			$value = $this->_identifier . $value . $this->_identifier;
		}

		if (isset($alias))
		{
			$value .= ' AS ' . $this->_identifier . $alias . $this->_identifier;
		}

		return $value;
	}

	/**
	 * Extracts the text between parentheses, if any.
	 *
	 *     // Returns: array('CHAR', '6')
	 *     list($type, $length) = $db->_parse_type('CHAR(6)');
	 *
	 * @param   string
	 * @return  array   list containing the type and length, if any
	 */
	protected function _parse_type($type)
	{
		if (($open = strpos($type, '(')) === FALSE)
		{
			// No length specified
			return array($type, NULL);
		}

		// Closing parenthesis
		$close = strpos($type, ')', $open);

		// Length without parentheses
		$length = substr($type, $open + 1, $close - 1 - $open);

		// Type without the length
		$type = substr($type, 0, $open) . substr($type, $close + 1);

		return array($type, $length);
	}


	public function list_columns($table, $like = NULL, $add_prefix = TRUE)
	{
		// Quote the table name
		$table = ($add_prefix === TRUE) ? $this->quote_table($table) : $table;

		if (is_string($like))
		{
			// Search for column names
			$res = $this->query(
				'SHOW FULL COLUMNS FROM ' . $table . ' LIKE ' . $this->quote($like))->fetch_all(MYSQLI_ASSOC);
		}
		else
		{
			// Find all column names
			$res = $this->query('SHOW FULL COLUMNS FROM ' . $table)->fetch_all(MYSQLI_ASSOC);
		}

		$count = 0;
		$columns = array();
		foreach ($res as $row)
		{
			list($type, $length) = $this->_parse_type($row['Type']);

			$column = $this->datatype($type);

			$column['column_name'] = $row['Field'];
			$column['column_default'] = $row['Default'];
			$column['data_type'] = $type;
			$column['is_nullable'] = ($row['Null'] == 'YES');
			$column['ordinal_position'] = ++$count;

			switch ($column['type'])
			{
				case 'float':
					if (isset($length))
					{
						list($column['numeric_precision'], $column['numeric_scale']) = explode(',', $length);
					}
					break;
				case 'int':
					if (isset($length))
					{
						// MySQL attribute
						$column['display'] = $length;
					}
					break;
				case 'string':
					switch ($column['data_type'])
					{
						case 'binary':
						case 'varbinary':
							$column['character_maximum_length'] = $length;
							break;
						case 'char':
						case 'varchar':
							$column['character_maximum_length'] = $length;
							break;
						case 'text':
						case 'tinytext':
						case 'mediumtext':
						case 'longtext':
							$column['collation_name'] = $row['Collation'];
							break;
						case 'enum':
						case 'set':
							$column['collation_name'] = $row['Collation'];
							$column['options'] = explode('\',\'', substr($length, 1, - 1));
							break;
					}
					break;
			}

			// MySQL attributes
			$column['comment'] = $row['Comment'];
			$column['extra'] = $row['Extra'];
			$column['key'] = $row['Key'];
			$column['privileges'] = $row['Privileges'];

			$columns[$row['Field']] = $column;
		}
		return $columns;
	}

	public function escape($value)
	{
		// Make sure the database is connected
		isset($this->_connection[$this->_is_slave]) or $this->connect();

		if (($value = $this->_connection[$this->_is_slave]->real_escape_string((string) $value)) === FALSE)
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, ':error',
				array(':error' => $this->_connection[$this->_is_slave]->error),
				$this->_connection[$this->_is_slave]->errno);
		}

		// SQL standard is to use single-quotes for all values
		return "'$value'";
	}

} // End Rest_Database


class Rest_Database_Result {
	/**
	 * @var Mysqli_Result
	 */
	protected $_result;

	public function __construct($result)
	{
		$this->_result = $result;
	}

	public function __destruct()
	{
		if ($this->_result)
		{
			$this->_result->free();
		}
	}

	public function __call($name, $args)
	{
		if ($args)
		{
			$result = call_user_func_array(array($this->_result, $name), (array)$args);
		}
		else
		{
			$result = call_user_func(array($this->_result, $name));
		}
		if (in_array($name, array('free', 'close', 'free_result')))
		{
			$this->_result = NULL;
		}
		return $result;
	}

	public function fetch_all($resulttype = MYSQLI_NUM)
	{
		// Compatibility layer with mysqli
		if (method_exists('mysqli_result', 'fetch_all')) {
			$res = $this->_result->fetch_all($resulttype);
		}
		else
		{
			for ($res = array(); $tmp = $this->_result->fetch_array($resulttype);)
			{
				$res[] = $tmp;
			}
		}
		return $res;
	}
}