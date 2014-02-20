<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * HTTT Caching adaptor class that provides caching services to the
 * [Request_Client] class, using HTTP cache control logic as defined in
 * RFC 2616.
 *
 * @package    Rest
 * @category   Base
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_HTTP_Cache {

	const CACHE_STATUS_KEY = 'x-cache-status';
	const CACHE_STATUS_SAVED = 'SAVED';
	const CACHE_STATUS_HIT = 'HIT';
	const CACHE_STATUS_MISS = 'MISS';
	const CACHE_HIT_KEY = 'x-cache-hits';

	const CACHE_UPDATE_KEY = 'x-cache-update';

	/**
	 * Factory method for Rest_HTTP_Cache that provides a convenient dependency
	 * injector for the Cache library.
	 *
	 *      // Create Rest_HTTP_Cache with named cache engine
	 *      $rest_http_cache = Rest_HTTP_Cache::factory('memcache', array(
	 *          'allow_private_cache' => FALSE
	 *          )
	 *      );
	 *
	 *      // Create Rest_HTTP_Cache with supplied cache engine
	 *      $http_cache = Rest_HTTP_Cache::factory(Cache::instance('memcache'),
	 *          array(
	 *              'allow_private_cache' => FALSE
	 *          )
	 *      );
	 *
	 * @uses    [Cache]
	 * @param   mixed $cache cache engine to use
	 * @param   array $options options to set to this class
	 * @return  Rest_HTTP_Cache
	 */
	public static function factory($cache, array $options = array())
	{
		if (! $cache instanceof Rest_Cache)
		{
			$cache = Rest_Cache::instance($cache);
		}

		$options['cache'] = $cache;

		return new Rest_HTTP_Cache($options);
	}

	/**
	 * Basic cache key generator that hashes the entire request and returns
	 * it. This is fine for static content, or dynamic content where user
	 * specific information is encoded into the request.
	 *
	 *      // Generate cache key
	 *      $cache_key = Rest_HTTP_Cache::basic_cache_key_generator($request);
	 *
	 * @param   Rest_Request $request
	 * @return  string
	 */
	public static function basic_cache_key_generator(Rest_Request $request)
	{
		$uri = $request->get_uri();
		$query = $request->query();
		$headers = $request->headers();
		return sha1($uri . '?' . http_build_query($query, NULL, '&') . '~' . implode('~', $headers));
	}

	/**
	 * Uri cache key generator that hashes the entire request and returns
	 * it. This is fine for static content, or dynamic content where user
	 * specific information is encoded into the request.
	 *
	 *      // Generate cache key
	 *      $cache_key = Rest_HTTP_Cache::uri_cache_key_generator($request);
	 *
	 * @param   Rest_Request $request
	 * @param   string       $uri
	 * @return  string
	 */
	public static function uri_cache_key_generator(Rest_Request $request, $uri = NULL)
	{
		if ($uri === NULL)
		{
			$uri = $request->get_uri();
		}
		$query = array('access_token' => $request->get_access_token());
		$headers = $request->headers();
		return sha1($uri . '?' . http_build_query($query, NULL, '&') . '~' . implode('~', $headers));
	}

	/**
	 * @var     Rest_Cache    cache driver to use for HTTP caching
	 */
	protected $_cache;

	/**
	 * @var    string  Cache key generator callback
	 */
	protected $_cache_key_callback;

	/**
	 * @var    boolean   Defines whether this client should cache `private` cache directives
	 * @see    http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
	 */
	protected $_allow_private_cache = FALSE;

	/**
	 * @var    int       The timestamp of the request
	 */
	protected $_request_time;

	/**
	 * @var    int       The timestamp of the response
	 */
	protected $_response_time;

	/**
	 * Constructor method for this class. Allows dependency injection of the
	 * required components such as `Cache` and the cache key generator.
	 *
	 * @param   array $options
	 */
	public function __construct(array $options = array())
	{
		foreach ($options as $key => $value)
		{
			if (method_exists($this, $key))
			{
				$this->$key($value);
			}
		}

		if ($this->_cache_key_callback === NULL)
		{
			$this->cache_key_callback('Rest_HTTP_Cache::basic_cache_key_generator');
		}
	}

	/**
	 * Executes the supplied [Request] with the supplied [Request_Client].
	 * Before execution, the Rest_HTTP_Cache adapter checks the request type,
	 * destructive requests such as `POST`, `PUT` and `DELETE` will bypass
	 * cache completely and ensure the response is not cached. All other
	 * Request methods will allow caching, if the rules are met.
	 *
	 * @param   Rest_Request $request client to execute with Cache-Control
	 * @return  Rest_Response|bool
	 */
	public function execute(Rest_Request $request)
	{
		if (! $this->_cache instanceof Rest_Cache)
		{
			return FALSE;
		}

		// If this is a destructive request, by-pass cache completely
		if (in_array($request->request_method,
			array(
			     'POST',
			     'PUT',
			     'DELETE'
			))
		)
		{
			// Kill existing caches for this request
			$this->invalidate_cache($request);

			$response = $request->execute();

			$cache_control = Rest_HTTP_Header::create_cache_control(
				array(
				     'no-cache',
				     'must-revalidate'
				));

			// Ensure client respects destructive action
			return $response->headers('Cache-Control', $cache_control);
		}

		// Create the cache key
		$cache_key = $this->create_cache_key($request, $this->_cache_key_callback);

		// Try and return cached version
		if (($response = $this->cache_response($cache_key, $request)) instanceof Rest_Response)
		{
			return $response;
		}

		// Start request time
		$this->_request_time = time();

		// Execute the request with the Request client
		$response = $request->execute();

		// Stop response time
		$this->_response_time = (time() - $this->_request_time);

		// Cache the response
		$this->cache_response($cache_key, $request, $response);

		$response->headers(Rest_HTTP_Cache::CACHE_STATUS_KEY,
			Rest_HTTP_Cache::CACHE_STATUS_MISS);

		return $response;
	}

	/**
	 * Invalidate a cached response for the [Request] supplied.
	 * This has the effect of deleting the response from the
	 * [Rest_Cache] entry.
	 *
	 * @param   Rest_Request  $request Response to remove from cache
	 * @return  void
	 */
	public function invalidate_cache(Rest_Request $request)
	{
		if (($cache = $this->cache()) instanceof Rest_Cache)
		{
			// Clean list uri cache
			$uris = explode('/', $request->get_uri());
			if ($uris >= 2 AND count($uris) % 2 == 0)
			{
				$this->invalidate_uri_cache($request, implode('/', $uris));
			}
			// Clean current uri cache
			$this->invalidate_uri_cache($request);
		}
		return;
	}

	/**
	 * Clean uri cache
	 * @param Rest_Request $request
	 * @param string $uri
	 */
	public function invalidate_uri_cache(Rest_Request $request, $uri = NULL)
	{
		$key = self::uri_cache_key_generator($request, $uri);
		$this->cache()->delete($key);
		$this->cache()->set(self::CACHE_UPDATE_KEY . $key, microtime(TRUE), Rest_Cache::CACHE_CEILING);
	}

	/**
	 * Getter and setter for the internal caching engine,
	 * used to cache responses if available and valid.
	 *
	 * @param   Rest_Cache $cache cache engine to use for caching
	 * @return  Rest_Cache
	 * @return
	 */
	public function cache(Rest_Cache $cache = NULL)
	{
		if ($cache === NULL)
		{
			return $this->_cache;
		}

		$this->_cache = $cache;
		return $this;
	}

	/**
	 * Gets or sets the [Request_Client::allow_private_cache] setting.
	 * If set to `TRUE`, the client will also cache cache-control directives
	 * that have the `private` setting.
	 *
	 * @see     http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
	 * @param   boolean  $setting allow caching of privately marked responses
	 * @return  boolean
	 * @return  [Request_Client]
	 */
	public function allow_private_cache($setting = NULL)
	{
		if ($setting === NULL)
		{
			return $this->_allow_private_cache;
		}

		$this->_allow_private_cache = (bool) $setting;
		return $this;
	}

	/**
	 * Sets or gets the cache key generator callback for this caching
	 * class. The cache key generator provides a unique hash based on the
	 * `Request` object passed to it.
	 *
	 * The default generator is [Rest_HTTP_Cache::basic_cache_key_generator()], which
	 * serializes the entire `HTTP_Request` into a unique sha1 hash. This will
	 * provide basic caching for static and simple dynamic pages. More complex
	 * algorithms can be defined and then passed into `Rest_HTTP_Cache` using this
	 * method.
	 *
	 *      // Get the cache key callback
	 *      $callback = $http_cache->cache_key_callback();
	 *
	 *      // Set the cache key callback
	 *      $http_cache->cache_key_callback('Foo::cache_key');
	 *
	 *      // Alternatively, in PHP 5.3 use a closure
	 *      $http_cache->cache_key_callback(function (Request $request) {
	 *            return sha1($request->render());
	 *      });
	 *
	 * @param   string $callback
	 * @return  mixed
	 * @throws  Rest_Exception
	 */
	public function cache_key_callback($callback = NULL)
	{
		if ($callback === NULL)
		{
			return $this->_cache_key_callback;
		}

		if (! is_callable($callback))
		{
			throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, 'cache_key_callback must be callable!');
		}

		$this->_cache_key_callback = $callback;
		return $this;
	}

	/**
	 * Creates a cache key for the request to use for caching
	 * [Rest_Response] returned by [Rest_Request::execute].
	 *
	 * This is the default cache key generating logic, but can be overridden
	 * by setting [Rest_HTTP_Cache::cache_key_callback()].
	 *
	 * @param Rest_Request $request request to create key for
	 * @param string $callback $callback optional callback to use instead of built-in method
	 * @return mixed|string
	 */
	public function create_cache_key(Rest_Request $request, $callback = NULL)
	{
		if (is_callable($callback))
		{
			return call_user_func($callback, $request);
		}
		else
		{
			return Rest_HTTP_Cache::basic_cache_key_generator($request);
		}
	}

	/**
	 * Controls whether the response can be cached. Uses HTTP
	 * protocol to determine whether the response can be cached.
	 *
	 * @link    RFC 2616 http://www.w3.org/Protocols/rfc2616/
	 * @param   Rest_Response  $response The Response
	 * @return  boolean
	 */
	public function set_cache(Rest_Response $response)
	{
		$headers = $response->headers();

		if ($cache_control = Rest_Arr::get($headers, 'cache-control'))
		{
			// Parse the cache control
			$cache_control = Rest_HTTP_Header::parse_cache_control($cache_control);

			// If the no-cache or no-store directive is set, return
			if (array_intersect($cache_control, array('no-cache', 'no-store')))
			{
				return FALSE;
			}

			// Check for private cache and get out of here if invalid
			if (! $this->_allow_private_cache AND in_array('private', $cache_control))
			{
				if (! isset($cache_control['s-maxage']))
				{
					return FALSE;
				}

				// If there is a s-maxage directive we can use that
				$cache_control['max-age'] = $cache_control['s-maxage'];
			}

			// Check that max-age has been set and if it is valid for caching
			if (isset($cache_control['max-age']) AND $cache_control['max-age'] < 1)
			{
				return FALSE;
			}
		}

		if ($expires = Rest_Arr::get($headers, 'expires') AND ! isset($cache_control['max-age']))
		{
			// Can't cache things that have expired already
			if (strtotime($expires) <= time())
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Caches a [Response] using the supplied [Cache]
	 * and the key generated by [Request_Client::_create_cache_key].
	 *
	 * If not response is supplied, the cache will be checked for an existing
	 * one that is available.
	 *
	 * @param   string  $key  the cache key to use
	 * @param   Rest_Request $request   the HTTP Request
	 * @param   Rest_Response $response the HTTP Response
	 * @return  mixed
	 */
	public function cache_response($key, Rest_Request $request, Rest_Response $response = NULL)
	{
		if (! $this->_cache instanceof Rest_Cache)
		{
			return FALSE;
		}

		// Check for Pragma: no-cache
		if ($pragma = $request->headers('pragma'))
		{
			if ($pragma == 'no-cache')
			{
				return FALSE;
			}
			elseif (is_array($pragma) AND in_array('no-cache', $pragma))
			{
				return FALSE;
			}
		}

		// If there is no response, lookup an existing cached response
		if ($response === NULL)
		{
			$data = $this->_cache->get($key);
			$response = isset($data['response']) ? $data['response'] : '';

			if (! $response instanceof Rest_Response)
			{
				return FALSE;
			}
			// Check cache time
			$cache_time = isset($data['update']) ? $data['update'] : 0;
			$update_time = $this->_cache->get(self::CACHE_UPDATE_KEY . self::uri_cache_key_generator($request));
			if ((float) $cache_time < (float) $update_time)
			{
				return FALSE;
			}

			// Do cache hit arithmetic
			$hit_count = $this->_cache->increment(Rest_HTTP_Cache::CACHE_HIT_KEY . $key);

			// Update the header to have correct HIT status and count
			$response->headers(Rest_HTTP_Cache::CACHE_STATUS_KEY,
				Rest_HTTP_Cache::CACHE_STATUS_HIT)
				->headers(Rest_HTTP_Cache::CACHE_HIT_KEY, $hit_count);

			return $response;
		}
		else
		{
			if (($ttl = $this->cache_lifetime($response)) === FALSE)
			{
				return FALSE;
			}

			$response->headers(Rest_HTTP_Cache::CACHE_STATUS_KEY,
				Rest_HTTP_Cache::CACHE_STATUS_SAVED);

			// Set the hit count to zero
			$this->_cache->set(Rest_HTTP_Cache::CACHE_HIT_KEY . $key, 0, $ttl);

			// Store response and cache time
			$data = array(
				'response' => $response,
				'update' => microtime(TRUE)
			);

			return $this->_cache->set($key, $data, $ttl);
		}
	}

	/**
	 * Calculates the total Time To Live based on the specification
	 * RFC 2616 cache lifetime rules.
	 *
	 * @param   Rest_Response  $response  Response to evaluate
	 * @return  mixed  TTL value or false if the response should not be cached
	 */
	public function cache_lifetime(Rest_Response $response)
	{
		// Get out of here if this cannot be cached
		if (! $this->set_cache($response))
		{
			return FALSE;
		}

		// Calculate apparent age
		if ($date = $response->headers('date'))
		{
			$apparent_age = max(0, $this->_response_time - strtotime($date));
		}
		else
		{
			$apparent_age = max(0, $this->_response_time);
		}

		// Calculate corrected received age
		if ($age = $response->headers('age'))
		{
			$corrected_received_age = max($apparent_age, intval($age));
		}
		else
		{
			$corrected_received_age = $apparent_age;
		}

		// Corrected initial age
		$corrected_initial_age = $corrected_received_age + $this->request_execution_time();

		// Resident time
		$resident_time = time() - $this->_response_time;

		// Current age
		$current_age = $corrected_initial_age + $resident_time;

		// Prepare the cache freshness lifetime
		$ttl = NULL;

		// Cache control overrides
		if ($cache_control = $response->headers('cache-control'))
		{
			// Parse the cache control header
			$cache_control = Rest_HTTP_Header::parse_cache_control($cache_control);

			if (isset($cache_control['max-age']))
			{
				$ttl = $cache_control['max-age'];
			}

			if (isset($cache_control['s-maxage']) AND isset($cache_control['private']) AND $this->_allow_private_cache)
			{
				$ttl = $cache_control['s-maxage'];
			}

			if (isset($cache_control['max-stale']) AND ! isset($cache_control['must-revalidate']))
			{
				$ttl = $current_age + $cache_control['max-stale'];
			}
		}

		// If we have a TTL at this point, return
		if ($ttl !== NULL)
		{
			return $ttl;
		}

		if ($expires = $response->headers('expires'))
		{
			return strtotime($expires) - $current_age;
		}

		return FALSE;
	}

	/**
	 * Returns the duration of the last request execution.
	 * Either returns the time of completed requests or
	 * `FALSE` if the request hasn't finished executing, or
	 * is yet to be run.
	 *
	 * @return  mixed
	 */
	public function request_execution_time()
	{
		if ($this->_request_time === NULL OR $this->_response_time === NULL)
		{
			return FALSE;
		}

		return $this->_response_time - $this->_request_time;
	}

} // End Rest_HTTP_Cache