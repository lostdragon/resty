<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Cache helper.
 *
 * @package    System
 * @category   Helpers
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Cache {

	/**
	 * Memcache default expire time
	 */
	const DEFAULT_EXPIRE = 3600;

	// Memcache has a maximum cache lifetime of 30 days
	const CACHE_CEILING = 2592000;

	/**
	 * @var   string     default driver to use
	 */
	public static $default = 'memcache';

	/**
	 * Memcache resource
	 *
	 * @var Memcache
	 */
	protected $_memcache;

	/**
	 * Flags to use when storing values
	 *
	 * @var string
	 */
	protected $_flags;

	/**
	 * The default configuration for the memcached server
	 *
	 * @var array
	 */
	protected $_default_config = array();

	/**
	 * @var array Rest_Config
	 */
	protected $_config = array();

	/**
	 * Constructs the Rest_Cache object
	 *
	 * @param   array $config configuration
	 * @throws  Rest_Exception
	 */
	protected function __construct(array $config)
	{
		// Check for the memcache extention
		if (! extension_loaded('memcache'))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Memcache PHP extension not loaded');
		}
		$this->config($config);

		// Setup Memcache
		$this->_memcache = new Memcache;

		// Load servers from configuration
		$servers = Rest_Arr::get($this->_config, 'servers', NULL);

		if (! $servers)
		{
			// Throw an exception if no server found
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'No Memcache servers defined in configuration');
		}

		// Setup default server configuration
		$this->_default_config = array(
			'host' => 'localhost',
			'port' => 11211,
			'persistent' => FALSE,
			'weight' => 1,
			'timeout' => 1,
			'retry_interval' => 15,
			'status' => TRUE,
			'instant_death' => TRUE,
			'failure_callback' => array($this, '_failed_request'),
		);

		// Add the memcache servers to the pool
		foreach ($servers as $server)
		{
			// Merge the defined config with defaults
			$server += $this->_default_config;

			if (! $this->_memcache->addServer($server['host'], $server['port'], $server['persistent'],
				$server['weight'], $server['timeout'], $server['retry_interval'], $server['status'],
				$server['failure_callback'])
			)
			{
				throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Memcache could not connect to host \':host\' using port \':port\'',
					array(':host' => $server['host'],
					      ':port' => $server['port']
					));
			}
		}

		// Setup the flags
		$this->_flags = Rest_Arr::get($this->_config, 'compression', FALSE) ? MEMCACHE_COMPRESSED : FALSE;
	}

	/**
	 * @var   Rest_Cache instances
	 */
	public static $instances = array();

	/**
	 * Creates a singleton of a Rest Cache group. If no group is supplied
	 * the __default__ cache group is used.
	 *
	 *     // Create an instance of the default group
	 *     $default_group = Rest_Cache::instance();
	 *
	 *     // Create an instance of a group
	 *     $foo_group = Rest_Cache::instance('foo');
	 *
	 *     // Access an instantiated group directly
	 *     $foo_group = Rest_Cache::$instances['default'];
	 *
	 * @param   string $group the name of the cache group to use [Optional]
	 * @return  Rest_Cache
	 * @throws  Rest_Exception
	 */
	public static function instance($group = NULL)
	{
		// If there is no group supplied
		if ($group === NULL)
		{
			// Use the default setting
			$group = Rest_Cache::$default;
		}

		if (isset(Rest_Cache::$instances[$group]))
		{
			// Return the current group if initiated already
			return Rest_Cache::$instances[$group];
		}

		$config = Rest_Config::get('cache.'.$group);

		if (empty($config))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR,
				'Failed to load Rest Cache group: :group',
				array(':group' => $group)
			);
		}

		// Create a new cache type instance
		Rest_Cache::$instances[$group] = new Rest_Cache($config);

		// Return the instance
		return Rest_Cache::$instances[$group];
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
		throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Cloning of Rest_Cache objects is forbidden');
	}

	/**
	 * Retrieve a cached value entry by id.
	 *
	 *     // Retrieve cache entry from memcache group
	 *     $data = Rest_Cache::instance('memcache')->get('foo');
	 *
	 *     // Retrieve cache entry from memcache group and return 'bar' if miss
	 *     $data = Rest_Cache::instance('memcache')->get('foo', 'bar');
	 *
	 * @param   string $id  id of cache to entry
	 * @param   string $default  default value to return if cache miss
	 * @return  mixed
	 * @throws  Rest_Exception
	 */
	public function get($id, $default = NULL)
	{
		// Get the value from Memcache
		$value = $this->_memcache->get($this->_sanitize_id($id));

		// If the value wasn't found, normalise it
		if ($value === FALSE)
		{
			$value = (NULL === $default) ? NULL : $default;
		}

		// Return the value
		return $value;
	}

	/**
	 * Set a value to cache with id and lifetime
	 *
	 *     $data = 'bar';
	 *
	 *     // Set 'bar' to 'foo' in memcache group for 10 minutes
	 *     if (Rest_Cache::instance('memcache')->set('foo', $data, 600))
	 *     {
	 *          // Rest_Cache was set successfully
	 *          return
	 *     }
	 *
	 * @param   string $id   id of cache entry
	 * @param   mixed   $data    data to set to cache
	 * @param   int $lifetime  lifetime in seconds, maximum value 2592000
	 * @return  bool
	 */
	public function set($id, $data, $lifetime = 3600)
	{
		// If the lifetime is greater than the ceiling
		if ($lifetime > Rest_Cache::CACHE_CEILING)
		{
			// Set the lifetime to maximum cache time
			$lifetime = Rest_Cache::CACHE_CEILING + time();
		}
		// Else if the lifetime is greater than zero
		elseif ($lifetime > 0)
		{
			$lifetime += time();
		}
		// Else
		else
		{
			// Normalise the lifetime
			$lifetime = 0;
		}

		// Set the data to memcache
		return $this->_memcache->set($this->_sanitize_id($id), $data, $this->_flags, $lifetime);
	}

	/**
	 * Delete a cache entry based on id
	 *
	 *     // Delete the 'foo' cache entry immediately
	 *     Rest_Cache::instance('memcache')->delete('foo');
	 *
	 *     // Delete the 'bar' cache entry after 30 seconds
	 *     Rest_Cache::instance('memcache')->delete('bar', 30);
	 *
	 * @param   string $id   id of entry to delete
	 * @param   int $timeout timeout of entry, if zero item is deleted immediately, otherwise the item will delete after the specified value in seconds
	 * @return  bool
	 */
	public function delete($id, $timeout = 0)
	{
		// Delete the id
		return $this->_memcache->delete($this->_sanitize_id($id), $timeout);
	}

	/**
	 * Delete all cache entries.
	 *
	 * Beware of using this method when
	 * using shared memory cache systems, as it will wipe every
	 * entry within the system for all clients.
	 *
	 *     // Delete all cache entries in the default group
	 *     Rest_Cache::instance('memcache')->delete_all();
	 *
	 * @return  bool
	 */
	public function delete_all()
	{
		$result = $this->_memcache->flush();

		// We must sleep after flushing, or overwriting will not work!
		// @see http://php.net/manual/en/function.memcache-flush.php#81420
		sleep(1);

		return $result;
	}

	/**
	 * Callback method for Memcache::failure_callback to use if any Memcache call
	 * on a particular server fails. This method switches off that instance of the
	 * server if the configuration setting `instant_death` is set to `TRUE`.
	 *
	 * @param   string $hostname
	 * @param   int $port
	 * @return  NULL|bool
	 */
	public function _failed_request($hostname, $port)
	{
		if (! $this->_config['instant_death'])
		{
			return NULL;
		}

		// Setup non-existent host
		$host = FALSE;

		// Get host settings from configuration
		foreach ($this->_config['servers'] as $server)
		{
			// Merge the defaults, since they won't always be set
			$server += $this->_default_config;
			// We're looking at the failed server
			if ($hostname == $server['host'] and $port == $server['port'])
			{
				// Server to disable, since it failed
				$host = $server;
				continue;
			}
		}

		if (! $host)
		{
			return NULL;
		}
		else
		{
			return $this->_memcache->setServerParams(
				$host['host'],
				$host['port'],
				$host['timeout'],
				$host['retry_interval'],
				FALSE, // Server is offline
				array($this, '_failed_request'
				));
		}
	}

	/**
	 * Increments a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string $id    id of cache entry to increment
	 * @param   int $step       step value to increment by
	 * @return  int|bool
	 */
	public function increment($id, $step = 1)
	{
		return $this->_memcache->increment($this->_sanitize_id($id), $step);
	}

	/**
	 * Decrements a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string $id   id of cache entry to decrement
	 * @param   int    $step   step value to decrement by
	 * @return  int|bool
	 */
	public function decrement($id, $step = 1)
	{
		return $this->_memcache->decrement($this->_sanitize_id($id), $step);
	}

	/**
	 * Replaces troublesome characters with underscores.
	 *
	 *     // Sanitize a cache id
	 *     $id = $this->_sanitize_id($id);
	 *
	 * @param   string $id id of cache to sanitize
	 * @return  string
	 */
	protected function _sanitize_id($id)
	{
		static $cache_pre;
		if(is_null($cache_pre)) {
			$cache_pre = $this->config('cache_pre');
		}
		// Change slashes and spaces to underscores
		return $cache_pre.str_replace(array('/', '\\', ' '), '_', $id);
	}
}
// End Rest_Cache
