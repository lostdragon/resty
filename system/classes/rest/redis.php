<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Cache helper.
 *
 * @package    System
 * @category   Helpers
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Redis {
	/**
	 * @var Redis
	 */
	protected $_redis = NULL;
	protected $_config = array();
	
	public static $instances = array();
	
	public static $default = 'default';
	public static $_default_config = array(
										'host' => 'localhost',
										'port'				=> 6379,
										'timeout'			=> 0,
										'persistent'		=> FALSE,
										'auth'				=>NULL,
										'serializer'		=>Redis::SERIALIZER_NONE, //SERIALIZER_NONE~不串行化，SERIALIZER_PHP~php内置串行化,SERIALIZER_IGBINARY~igbinary串行化
										'prefix'			=>NULL,
									);
									
	protected function __construct(array $config)
	{
		// Check for the redis extention
		if (! extension_loaded('redis'))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'Redis PHP extension not loaded');
		}
		$this->config($config);
		$this->_config =  $this->_config + Rest_Redis::$_default_config;
	}
	
	public function  __destruct()
	{
		if($this->_redis)
		{
			$this->_redis->close();
		}	
	}
	
	protected function reconnect()
	{	
		$this->_redis = new Redis();
		if($this->_config['persistent'])
		{
			$ret = $this->_redis->pconnect($this->_config['hostname'],$this->_config['port'],$this->_config['timeout']);
		}
		else
		{
			$ret = $this->_redis->connect($this->_config['hostname'],$this->_config['port'],$this->_config['timeout']);
		}

		if(!$ret)
		{
			return $ret;
		}
		
		if($this->_config['auth'])
		{
			$ret = $this->_redis->auth($this->_config['auth']);
			if(!$ret)
			{
				return $ret;
			}
		}

		if($this->_config['serializer'])
		{
			$this->_redis->setOption(Redis::OPT_SERIALIZER, $this->_config['serializer']);
		}
		
		if($this->_config['prefix'])
		{
			$this->_redis->setOption(Redis::OPT_PREFIX, $this->_config['prefix']);
		}
		
		return TRUE;
	}

	/**
	 * @param string $group
	 * @return Rest_Redis|Redis
	 * @throws Rest_Exception
	 */
	public static function instance($group = NULL)
	{
		// If there is no group supplied
		if ($group === NULL)
		{
			// Use the default setting
			$group = Rest_Redis::$default;
		}

		if (isset(Rest_Redis::$instances[$group]))
		{
			// Return the current group if initiated already
			return Rest_Redis::$instances[$group];
		}

		$config = Rest_Config::get('redis.'.$group);

		if (empty($config))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR,
				'Failed to load Rest redis group: :group',
				array(':group' => $group)
			);
		}

		// Create a new cache type instance
		Rest_Redis::$instances[$group] = new Rest_Redis($config);

		// Return the instance
		return Rest_Redis::$instances[$group];
	}
	
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
	
	public function __call($name, $args)
	{
		try
		{
			if(!$this->_redis)
			{
				$this->reconnect();
			}
			
			if ($args)
			{
				$result = call_user_func_array(array($this->_redis, $name), $args);
			}
			else
			{
				$result = call_user_func(array($this->_redis, $name));
			}			
		}
		catch(Exception $e)
		{
			$result = NULL;
		}
		
		return $result;
	}

}
// End Rest_Cache
