<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * URL helper class.
 *
 * @package    System
 * @category   Helpers
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_URL {

	/**
	 * Gets the base URL to the application.
	 * To specify a protocol, provide the protocol as a string or request object.
	 * If a protocol is used, a complete URL will be generated using the
	 * `$_SERVER['HTTP_HOST']` variable.
	 *
	 *     // Absolute URL path with no host or protocol
	 *     echo URL::base();
	 *
	 *     // Absolute URL path with host, https protocol and index.php if set
	 *     echo URL::base('https', TRUE);
	 *
	 * @param   mixed    $protocol Protocol string
	 * @param   boolean  $index    Add index file to URL?
	 * @return  string
	 * @uses    Rest::$index_file
	 */
	public static function base($protocol = NULL, $index = FALSE)
	{
		static $result;
		$key = md5($protocol . $index);
		if (! isset($result[$key]))
		{
			// Start with the configured base URL
			$base_url = Rest::$base_url;
			if (is_null($protocol))
			{
				// Use the initial request to get the protocol
				list($protocol) = explode('/',
					strtolower(isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1'));
			}

			if (! $protocol)
			{
				// Use the configured default protocol
				$protocol = parse_url($base_url, PHP_URL_SCHEME);
			}
			if ($index === TRUE AND ! empty(Rest::$index_file))
			{
				// Add the index file to the URL
				$base_url .= Rest::$index_file . '/';
			}

			if (is_string($protocol))
			{
				if ($port = parse_url($base_url, PHP_URL_PORT))
				{
					// Found a port, make it usable for the URL
					$port = ':' . $port;
				}

				if ($domain = parse_url($base_url, PHP_URL_HOST))
				{
					// Remove everything but the path from the URL
					$base_url = parse_url($base_url, PHP_URL_PATH);
				}
				else
				{
					// Attempt to use HTTP_HOST and fallback to SERVER_NAME
					$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
				}

				// Add the protocol and domain to the base URL
				$base_url = $protocol . '://' . $domain . $port . $base_url;
			}
			$result[$key] = $base_url;
		}
		return $result[$key];
	}

	/**
	 * Fetches an absolute site URL based on a URI segment.
	 *
	 *     echo URL::site('foo/bar');
	 *
	 * @param   string  $uri        Site URI to convert
	 * @param   mixed   $protocol   Protocol string
	 * @param   boolean $index        Include the index_page in the URL
	 * @return  string
	 * @uses    URL::base
	 */
	public static function site($uri = '', $protocol = NULL, $index = TRUE)
	{
		// Chop off possible scheme, host, port, user and pass parts
		$path = preg_replace('~^[-a-z0-9+.]++://[^/]++/?~', '', trim($uri, '/'));

		if (! self::is_ascii($path))
		{
			// Encode all non-ASCII characters, as per RFC 1738
			$path = preg_replace('~([^/]+)~e', 'rawurlencode("$1")', $path);
		}

		// Concat the URL
		return self::base($protocol, $index) . $path;
	}

	/**
	 * Tests whether a string contains only 7-bit ASCII bytes. This is used to
	 * determine when to use native functions or UTF-8 functions.
	 *
	 *     $ascii = UTF8::is_ascii($str);
	 *
	 * @param   mixed    string or array of strings to check
	 * @return  boolean
	 */
	public static function is_ascii($str)
	{
		if (is_array($str))
		{
			$str = implode($str);
		}

		return ! preg_match('/[^\x00-\x7F]/S', $str);
	}

} // End url