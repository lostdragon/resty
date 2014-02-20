<?php
/**
 * Mongo Helpers
 *
 * @package    System
 * @category   Database
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Mongo {

	/**
	 * @var string default instance name
	 */
	public static $default = 'default';

	/**
	 * @var array Mongo instances
	 */
	public static $instances = array();

	/**
	 * Get a singleton MangoDB instance. If configuration is not specified,
	 * it will be loaded from the MangoDB configuration file using the same
	 * group as the name.
	 *
	 * // Load the default database
	 * $db = Rest_Mongo::instance();
	 *
	 * // Create a custom configured instance
	 * $db = Rest_Mongo::instance('custom', $config);
	 *
	 * @param string $name instance name
	 * @param array $config configuration parameters
	 * @return Rest_Mongo
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = Rest_Mongo::$default;
		}

		if (! isset(Rest_Mongo::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this database
				$config = Rest_Config::get('mongo.' . $name);
			}

			new Rest_Mongo($name, $config);
		}

		return self::$instances[$name];
	}

	// Instance name
	protected $_name;

	// Connected
	protected $_connected = FALSE;

	/**
	 * Raw server connection
	 * @var Mongo
	 */
	protected $_connection;

	/**
	 * Raw database connection
	 * @var MongoDB
	 */
	protected $_db;

	// Store config locally
	protected $_config;

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
		$this->_name = $name;

		$this->_config = $config;

		// Store the database instance
		Rest_Mongo::$instances[$name] = $this;
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
		return $this->_name;
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
		if ($this->_connection)
		{
			return;
		}

		// Extract the connection parameters, adding required variables
		$hostname = 'localhost:27017';
		$database = '';

		extract($this->_config['connection']);

		if (isset($username) && isset($password))
		{
			// Add Username & Password to server string
			$hostname = $username . ':' . $password . '@' . $hostname . '/' . $database;
		}

		if (strpos($hostname, 'mongodb://') !== 0)
		{
			// Add required 'mongodb://' prefix
			$hostname = 'mongodb://' . $hostname;
		}

		if (! isset($options))
		{
			$options = array();
		}

		// We connect below in a separate try catch
		$options['connect'] = FALSE;

		// Create connection object
		$this->_connection = new Mongo($hostname, $options);

		try
		{
			$this->_connection->connect();
		} catch (MongoConnectionException $e)
		{
			// Unable to connect to the database server
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Unable to connect to MongoDB server at :hostname',
				array(':hostname' => $e->getMessage()));
		}

		if (! isset($database))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'No database specified in MangoDB Config');
		}

		$this->_db = $this->_connection->selectDB($database);

		$this->_connected = TRUE;
	}

	/**
	 * Disconnect from the database. This is called automatically by [Rest_Mongo::__destruct].
	 * Clears the database instance from [Rest_Mongo::$instances].
	 *
	 *     $db->disconnect();
	 *
	 * @return  boolean
	 */
	public function disconnect()
	{
		if ($this->_connection)
		{
			$this->_connection->close();
		}

		$this->_db = $this->_connection = $this->_connected = NULL;
	}

	/** Database Management */

	/**
	 * Check if there was an error on the most recent db operation performed
	 * @return array|null
	 */
	public function last_error()
	{
		return $this->_connected
			? $this->_db->lastError()
			: NULL;
	}

	/**
	 * Checks for the last error thrown during a database operation
	 * @return array|null
	 */
	public function prev_error()
	{
		return $this->_connected
			? $this->_db->prevError()
			: NULL;
	}

	/**
	 * Clears any flagged errors on the database
	 * @return array|null
	 */
	public function reset_error()
	{
		return $this->_connected
			? $this->_db->resetError()
			: NULL;
	}

	/**
	 * Execute a database command
	 *
	 * @param array $command The query to send.
	 * @param array $options This parameter is an associative array of the form array("optionname" => <boolean>, ...)
	 * @return array
	 */
	public function command(array $command, array $options)
	{
		return $this->_call('command', array('options' => $options), $command);
	}

	/**
	 * Runs JavaScript code on the database server.
	 *
	 * @param MongoCode|string $code MongoCode or string to execute.
	 * @param array $args Arguments to be passed to code.
	 * @return array
	 */
	public function execute($code, array $args = array())
	{
		return $this->_call('execute', array(
		                                    'code' => $code,
		                                    'args' => $args
		                               ));
	}

	/**
	 * get MongoDB instance
	 *
	 * @return bool|MongoDB
	 */
	public function db()
	{
		return $this->_connected
			? $this->_db
			: FALSE;
	}

	/** Collection management */

	/**
	 * Creates a collection
	 *
	 * @param string $name The name of the collection.
	 * @param bool $capped If the collection should be a fixed size.
	 * @param int $size If the collection is fixed size, its size in bytes.
	 * @param int $max If the collection is fixed size, the maximum number of elements to store in the collection.
	 * @return MongoCollection
	 */
	public function create_collection($name, $capped = FALSE, $size = 0, $max = 0)
	{
		return $this->_call('create_collection', array(
		                                              'name' => $name,
		                                              'capped' => $capped,
		                                              'size' => $size,
		                                              'max' => $max
		                                         ));
	}

	/**
	 * Drops this database
	 *
	 * @param string $collection_name
	 * @return array
	 */
	public function drop_collection($collection_name)
	{
		return $this->_call('drop_collection', array(
		                                            'collection_name' => $collection_name
		                                       ));
	}

	/**
	 *  Creates an index on the given field(s), or does nothing if the index already exists
	 *
	 * @param string $collection_name
	 * @param array $keys <p>
	 *      An array of fields by which to sort the index on. Each element in the array has as key the field name,
	 *       and as value either 1 for ascending sort, or -1 for descending sort.</p>
	 * @param array $options This parameter is an associative array of the form array("optionname" => <boolean>, ...)
	 * @return bool
	 */
	public function ensure_index($collection_name, $keys, $options = array())
	{
		return $this->_call('ensure_index', array(
		                                         'collection_name' => $collection_name,
		                                         'keys' => $keys,
		                                         'options' => $options
		                                    ));
	}

	/** Data Management */

	/**
	 * Inserts multiple documents into this collection
	 *
	 * @param string $collection_name
	 * @param array $a An array of arrays.
	 * @param array $options Options for the inserts.
	 * @return array|bool
	 */
	public function batch_insert($collection_name, array $a, array $options = array())
	{
		return $this->_call('batch_insert', array(
		                                         'collection_name' => $collection_name,
		                                         'options' => $options
		                                    ), $a);
	}

	/**
	 * Counts the number of documents in collection
	 *
	 * @param string $collection_name
	 * @param array $query Associative array or object with fields to match.
	 * @param int $limit Specifies an upper limit to the number returned.
	 * @param int $skip Specifies a number of results to skip before starting the count.
	 * @return int
	 */
	public function count($collection_name, array $query = array(), $limit = 0, $skip = 0)
	{
		return $this->_call('count', array(
		                                  'collection_name' => $collection_name,
		                                  'query' => $query,
		                                  'limit' => $limit,
		                                  'skip' => $skip
		                             ));
	}

	/**
	 * Query collection, returning a single element
	 * @param string $collection_name collection name
	 * @param array $query array()
	 * @param array $fields Fields of the results to return. <p>
	 * The array is in the format array('fieldname' => true, 'fieldname2' => true). The _id field is always returned.
	 * </p>
	 * @return array|NULL
	 */
	public function find_one($collection_name, array $query = array(), array $fields = array())
	{
		return $this->_call('find_one', array(
		                                     'collection_name' => $collection_name,
		                                     'query' => $query,
		                                     'fields' => $fields
		                                ));
	}

	/**
	 * Querys this collection, returning a MongoCursor for the result set
	 * @param string $collection_name
	 * @param array $query The fields for which to search
	 * @param array $fields Fields of the results to return
	 * @return MongoCursor
	 */
	public function find($collection_name, array $query = array(), array $fields = array())
	{
		return $this->_call('find', array(
		                                 'collection_name' => $collection_name,
		                                 'query' => $query,
		                                 'fields' => $fields
		                            ));
	}

	/**
	 * Performs an operation similar to SQL's GROUP BY command
	 * @param string $collection_name
	 * @param array $keys Fields to group by. If an array or non-code object is passed, it will be the key used to group results.
	 * @param array $initial Initial value of the aggregation counter object.
	 * @param string $reduce A function that takes two arguments (the current document and the aggregation to this point) and does the aggregation.
	 * @param array $condition Optional parameters to the group command.
	 * @return array
	 */
	public function group($collection_name, $keys, array $initial, $reduce, array $condition = array())
	{
		return $this->_call('group', array(
		                                  'collection_name' => $collection_name,
		                                  'keys' => $keys,
		                                  'initial' => $initial,
		                                  'reduce' => $reduce,
		                                  'condition' => $condition
		                             ));
	}

	/**
	 * Update records based on a given criteria
	 *
	 * @param string $collection_name
	 * @param array $criteria  Description of the objects to update.
	 * @param array $newObj The object with which to update the matching records.
	 * @param array $options This parameter is an associative array of the form array("optionname" => <boolean>, ...)
	 * @return array|bool
	 */
	public function update($collection_name, array $criteria, array $newObj, $options = array())
	{
		return $this->_call('update', array(
		                                   'collection_name' => $collection_name,
		                                   'criteria' => $criteria,
		                                   'options' => $options
		                              ), $newObj);
	}

	/**
	 *  Inserts an array into the collection
	 *
	 * @param string $collection_name
	 * @param array $a An array.
	 * @param array $options Options for the insert.
	 * @return array|bool
	 */
	public function insert($collection_name, array $a, $options = array())
	{
		return $this->_call('insert', array(
		                                   'collection_name' => $collection_name,
		                                   'options' => $options
		                              ), $a);
	}

	/**
	 * Remove records from this collection
	 *
	 * @param string $collection_name
	 * @param array $criteria Description of records to remove.
	 * @param array $options Options for remove.
	 * @return array|bool
	 */
	public function remove($collection_name, array $criteria, $options = array())
	{
		return $this->_call('remove', array(
		                                   'collection_name' => $collection_name,
		                                   'criteria' => $criteria,
		                                   'options' => $options
		                              ));
	}

	/**
	 * Saves an object to this collection
	 *
	 * @param string $collection_name
	 * @param array $a Array to save.
	 * @param array $options Options for the save.
	 * @return array|bool
	 */
	public function save($collection_name, array $a, $options = array())
	{
		return $this->_call('save', array(
		                                 'collection_name' => $collection_name,
		                                 'options' => $options
		                            ), $a);
	}

	/** File management */

	/**
	 * Fetches toolkit for dealing with files stored in this database
	 *
	 * @param string $arg1 The prefix for the files and chunks collections.
	 * @param null $arg2
	 * @return MongoGridFS
	 */
	public function gridFS($arg1 = NULL, $arg2 = NULL)
	{
		$this->_connected OR $this->connect();

		if (! isset($arg1))
		{
			$arg1 = isset($this->_config['gridFS']['arg1'])
				? $this->_config['gridFS']['arg1']
				: 'fs';
		}

		if (! isset($arg2) && isset($this->_config['gridFS']['arg2']))
		{
			$arg2 = $this->_config['gridFS']['arg2'];
		}

		return $this->_db->getGridFS($arg1, $arg2);
	}

	/**
	 * Returns a single file matching the criteria
	 *
	 * @param array $criteria The filename or criteria for which to search.
	 * @return MongoGridFSFile|null
	 */
	public function get_file(array $criteria = array())
	{
		return $this->_call('get_file', array(
		                                     'criteria' => $criteria
		                                ));
	}

	/**
	 * Queries for files
	 *
	 * @param array $query The query.
	 * @param array $fields Fields to return.
	 * @return MongoGridFSCursor
	 */
	public function get_files(array $query = array(), array $fields = array())
	{
		return $this->_call('get_files', array(
		                                      'query' => $query,
		                                      'fields' => $fields
		                                 ));
	}

	/**
	 * Chunk fies and stores bytes in the database
	 *
	 * @param string $bytes A string of bytes to store.
	 * @param array $extra Other metadata to add to the file saved.
	 * @param array $options Options for the store.
	 * @return mixed
	 */
	public function set_file_bytes($bytes, array $extra = array(), array $options = array())
	{
		return $this->_call('set_file_bytes', array(
		                                           'bytes' => $bytes,
		                                           'extra' => $extra,
		                                           'options' => $options
		                                      ));
	}

	/**
	 * Stores a file in the database
	 *
	 * @param string $filename The name of the file.
	 * @param array $extra Other metadata to add to the file saved.
	 * @param array $options  Options for the store.
	 * @return mixed
	 */
	public function set_file($filename, array $extra = array(), array $options = array())
	{
		return $this->_call('set_file', array(
		                                     'filename' => $filename,
		                                     'extra' => $extra,
		                                     'options' => $options
		                                ));
	}

	/**
	 * Removes files from the collections
	 *
	 * @param array $criteria The filename or criteria for which to search.
	 * @param array $options Options for the remove
	 * @return bool
	 */
	public function remove_file(array $criteria = array(), array $options = array())
	{
		return $this->_call('remove_file', array(
		                                        'criteria' => $criteria,
		                                        'options' => $options
		                                   ));
	}

	/**
	 * All commands are executed by this method
	 *
	 * This allows for easy benchmarking
	 */
	protected function _call($command, array $arguments = array(), array $values = NULL)
	{
		$this->_connected OR $this->connect();

		extract($arguments);

		if (isset($collection_name))
		{
			$c = $this->_db->selectCollection($collection_name);
		}

		switch ($command)
		{
			case 'ensure_index':
				$r = $c->ensureIndex($keys, $options);
				break;
			case 'create_collection':
				$r = $this->_db->createCollection($name, $capped, $size, $max);
				break;
			case 'drop_collection':
				$r = $c->drop();
				break;
			case 'command':
				$r = $this->_db->command($values, $options);
				break;
			case 'execute':
				$r = $this->_db->execute($code, $args);
				break;
			case 'batch_insert':
				$r = $c->batchInsert($values, $options);
				break;
			case 'count':
				$r = $c->count($query, $limit, $skip);
				break;
			case 'find_one':
				$r = $c->findOne($query, $fields);
				break;
			case 'find':
				$r = $c->find($query, $fields);
				break;
			case 'group':
				$r = $c->group($keys, $initial, $reduce, $condition);
				break;
			case 'update':
				$r = $c->update($criteria, $values, $options);
				break;
			case 'insert':
				$r = $c->insert($values, $options);
				break;
			case 'remove':
				$r = $c->remove($criteria, $options);
				break;
			case 'save':
				$r = $c->save($values, $options);
				break;
			case 'get_file':
				$r = $this->gridFS()->findOne($criteria);
				break;
			case 'get_files':
				$r = $this->gridFS()->find($query, $fields);
				break;
			case 'set_file_bytes':
				$r = $this->gridFS()->storeBytes($bytes, $extra, $options);
				break;
			case 'set_file':
				$r = $this->gridFS()->storeFile($filename, $extra, $options);
				break;
			case 'remove_file':
				$r = $this->gridFS()->remove($criteria, $options);
				break;
		}

		return $r;
	}
}