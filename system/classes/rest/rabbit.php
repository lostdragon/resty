<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * RabbitMQ Helpers
 *
 * @package    System
 * @category   Helpers
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Rabbit {

	/**
	 * @var   string     default group to use
	 */
	public static $default = 'default';

	/**
	 * AMQPConnect resource
	 *
	 * @var AMQPConnect
	 */
	protected $_connection;

	/**
	 * The default configuration for the RabbitMQ server
	 *
	 * @var array
	 */
	protected $_default_config = array();

	/**
	 * @var  Rest_Config
	 */
	protected $_config = array();

	/**
	 * @var array
	 */
	protected static $_exchanges = array();

	/**
	 * Constructs the Rest_Rabbit object
	 *
	 * @param   array $config configuration
	 * @throws  Rest_Exception
	 */
	protected function __construct(array $config)
	{
		// Check for the rabbit extension
		if (! extension_loaded('rabbit'))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Rabbit PHP extension not loaded');
		}
		$this->config($config);

		// Setup default server configuration
		$this->_default_config =
			array('host' => 'localhost',
			      'port' => '5672',
			      'login' => 'guest',
			      'password' => 'guest',
			      'vhost' => '/'
			);

		$this->_config += $this->_default_config;
	}

	/**
	 * @var   Rest_Rabbit instances
	 */
	public static $instances = array();

	/**
	 * Creates a singleton of a Rest_Rabbit group. If no group is supplied
	 * the __default__ rabbit group is used.
	 *
	 *     // Create an instance of the default group
	 *     $default_group = Rest_Rabbit::instance();
	 *
	 *     // Create an instance of a group
	 *     $foo_group = Rest_Rabbit::instance('foo');
	 *
	 *     // Access an instantiated group directly
	 *     $foo_group = Rest_Rabbit::$instances['default'];zz
	 *
	 * @param   string $group the name of the rabbit group to use [Optional]
	 * @return  Rest_Rabbit
	 * @throws  Rest_Exception
	 */
	public static function instance($group = NULL)
	{
		// If there is no group supplied
		if ($group === NULL)
		{
			// Use the default setting
			$group = Rest_Rabbit::$default;
		}

		if (isset(Rest_Rabbit::$instances[$group]))
		{
			// Return the current group if initiated already
			return Rest_Rabbit::$instances[$group];
		}

		$config = Rest_Config::get('rabbit.' . $group);

		if (empty($config))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR,
				'Failed to load Rest Rabbit group: :group',
				array(':group' => $group)
			);
		}

		// Create a new RabbitMQ instance
		Rest_Rabbit::$instances[$group] = new Rest_Rabbit($config);

		// Return the instance
		return Rest_Rabbit::$instances[$group];
	}

	/**
	 * Getter and setter for the configuration. If no argument provided, the
	 * current configuration is returned. Otherwise the configuration is set
	 * to this class.
	 *
	 *     // Overwrite all configuration
	 *     $cache->config(array('driver' => 'memcache', '...'));
	 *
	 *     // Set a new configuration setting
	 *     $cache->config('servers', array(
	 *          'foo' => 'bar',
	 *          '...'
	 *          ));
	 *
	 *     // Get a configuration setting
	 *     $servers = $cache->config('servers);
	 *
	 * @param   mixed $key   key to set to array, either array or config path
	 * @param   mixed $value   value to associate with key
	 * @return  mixed
	 */
	public function config($key = NULL, $value = NULL)
	{
		if ($key === NULL)
		{
			return $this->_config;
		}

		if (is_array($key))
		{
			$this->_config = $key;
		}
		else
		{
			if ($value === NULL)
			{
				return Rest_Arr::get($this->_config, $key);
			}

			$this->_config[$key] = $value;
		}

		return $this;
	}

	/**
	 * Overload the __clone() method to prevent cloning
	 *
	 * @return  void
	 * @throws  Rest_Exception
	 */
	final public function __clone()
	{
		throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Cloning of Rest_Rabbit objects is forbidden');
	}

	/**
	 * Connect to the database. This is called automatically when the first
	 * query is executed.
	 *
	 *     $rabbit->connect();
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

		try
		{
			$this->_connection = new AMQPConnect($this->_config);
		} catch (Exception $e)
		{
			// No connection exists
			$this->_connection = NULL;

			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, ':error',
				array(':error' => $e->getMessage()),
				$e->getCode());
		}
	}

	/**
	 * Get an exchange instance.
	 *
	 *     $exchange = Rest_Rabbit::instance()->exchange('foo');
	 *
	 * @param   string $name  exchange name
	 * @return  AMQPExchange
	 * @throws  Rest_Exception
	 */
	public function exchange($name)
	{
		// Make sure the RabbitMQ is connected
		$this->_connection or $this->connect();

		if (! isset(self::$_exchanges[$name]))
		{
			try
			{
				self::$_exchanges[$name] = new AMQPExchange($this->_connection, $name);
			} catch (Exception $e)
			{
				throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, ':error',
					array(':error' => $e->getMessage()),
					$e->getCode());
			}
		}
		// Return the value
		return self::$_exchanges[$name];
	}

	/**
	 * publish message
	 *
	 * @param   string $message   message
	 * @param   string $route_key route key
	 * @param   string $exchange  exchange name
	 * @throws  Rest_Exception
	 * @return  bool
	 */
	public function publish($message, $route_key, $exchange)
	{
		$exchange = $this->exchange($exchange);
		try
		{
			if (is_array($message))
			{
				$message = json_encode($message);
			}
			return $exchange->publish($message, $route_key);
		} catch (Exception $e)
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, ':error',
				array(':error' => $e->getMessage()),
				$e->getCode());
		}
	}

}
// End Rest_Rabbit
