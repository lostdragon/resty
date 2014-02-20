<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * The Rest_HTTP_Header class provides an Object-Orientated interface
 * to HTTP headers. This can parse header arrays returned from the
 * PHP functions `apache_request_headers()` or the `http_parse_headers()`
 * function available within the PECL HTTP library.
 *
 * @package    Rest
 * @category   HTTP
 * @author     Momo Team
 * @since      3.1.0
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_HTTP_Header extends ArrayObject {

	// Default Accept-* quality value if none supplied
	const DEFAULT_QUALITY = 1;

	/**
	 * Parses an Accept(-*) header and detects the quality
	 *
	 * @param   array $parts accept header parts
	 * @return  array
	 * @since   3.2.0
	 */
	public static function accept_quality(array $parts)
	{
		$parsed = array();

		// Resource light iteration
		$parts_keys = array_keys($parts);
		foreach ($parts_keys as $key)
		{
			$value = trim(str_replace(array("\r", "\n"), '', $parts[$key]));

			$pattern = '~\b(\;\s*+)?q\s*+=\s*+([.0-9]+)~';

			// If there is no quality directive, return default
			if (! preg_match($pattern, $value, $quality))
			{
				$parsed[$value] = (float) Rest_HTTP_Header::DEFAULT_QUALITY;
			}
			else
			{
				$quality = $quality[2];

				if ($quality[0] === '.')
				{
					$quality = '0' . $quality;
				}

				// Remove the quality value from the string and apply quality
				$parsed[trim(preg_replace($pattern, '', $value, 1), '; ')] = (float) $quality;
			}
		}

		return $parsed;
	}

	/**
	 * Parses the accept header to provide the correct quality values
	 * for each supplied accept type.
	 *
	 * @see     http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.1
	 * @param   string $accepts  accept content header string to parse
	 * @return  array
	 * @since   3.2.0
	 */
	public static function parse_accept_header($accepts = NULL)
	{
		$accepts = explode(',', (string) $accepts);

		// If there is no accept, lets accept everything
		if ($accepts === NULL)
		{
			return array('*' => array('*' => (float) Rest_HTTP_Header::DEFAULT_QUALITY));
		}

		// Parse the accept header qualities
		$accepts = Rest_HTTP_Header::accept_quality($accepts);

		$parsed_accept = array();

		// This method of iteration uses less resource
		$keys = array_keys($accepts);
		foreach ($keys as $key)
		{
			// Extract the parts
			$parts = explode('/', $key, 2);

			// Invalid content type- bail
			if (! isset($parts[1]))
			{
				continue;
			}

			// Set the parsed output
			$parsed_accept[$parts[0]][$parts[1]] = $accepts[$key];
		}

		return $parsed_accept;
	}

	/**
	 * Parses the `Accept-Charset:` HTTP header and returns an array containing
	 * the charset and associated quality.
	 *
	 * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.2
	 * @param   string $charset  charset string to parse
	 * @return  array
	 * @since   3.2.0
	 */
	public static function parse_charset_header($charset = NULL)
	{
		if ($charset === NULL)
		{
			return array('*' => (float) Rest_HTTP_Header::DEFAULT_QUALITY);
		}

		return Rest_HTTP_Header::accept_quality(explode(',', (string) $charset));
	}

	/**
	 * Parses the `Accept-Encoding:` HTTP header and returns an array containing
	 * the charsets and associated quality.
	 *
	 * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.3
	 * @param   string $encoding  charset string to parse
	 * @return  array
	 * @since   3.2.0
	 */
	public static function parse_encoding_header($encoding = NULL)
	{
		// Accept everything
		if ($encoding === NULL)
		{
			return array('*' => (float) Rest_HTTP_Header::DEFAULT_QUALITY);
		}
		elseif ($encoding === '')
		{
			return array('identity' => (float) Rest_HTTP_Header::DEFAULT_QUALITY);
		}
		else
		{
			return Rest_HTTP_Header::accept_quality(explode(',', (string) $encoding));
		}
	}

	/**
	 * Parses the `Accept-Language:` HTTP header and returns an array containing
	 * the languages and associated quality.
	 *
	 * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
	 * @param   string $language  charset string to parse
	 * @return  array
	 * @since   3.2.0
	 */
	public static function parse_language_header($language = NULL)
	{
		if ($language === NULL)
		{
			return array('*' => array('*' => (float) Rest_HTTP_Header::DEFAULT_QUALITY));
		}

		$language = Rest_HTTP_Header::accept_quality(explode(',', (string) $language));

		$parsed_language = array();

		$keys = array_keys($language);
		foreach ($keys as $key)
		{
			// Extract the parts
			$parts = explode('-', $key, 2);

			// Invalid content type- bail
			if (! isset($parts[1]))
			{
				$parsed_language[$parts[0]]['*'] = $language[$key];
			}
			else
			{
				// Set the parsed output
				$parsed_language[$parts[0]][$parts[1]] = $language[$key];
			}
		}

		return $parsed_language;
	}

	/**
	 * Generates a Cache-Control HTTP header based on the supplied array.
	 *
	 *     // Set the cache control headers you want to use
	 *     $cache_control = array(
	 *         'max-age'          => 3600,
	 *         'must-revalidate',
	 *         'public'
	 *     );
	 *
	 *     // Create the cache control header, creates :
	 *     // cache-control: max-age=3600, must-revalidate, public
	 *     $response->headers('Cache-Control', Rest_HTTP_Header::create_cache_control($cache_control);
	 *
	 * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec13.html#sec13
	 * @param   array  $cache_control   Cache-Control to render to string
	 * @return  string
	 */
	public static function create_cache_control(array $cache_control)
	{
		$parts = array();

		foreach ($cache_control as $key => $value)
		{
			$parts[] = (is_int($key)) ? $value : ($key . '=' . $value);
		}

		return implode(', ', $parts);
	}

	/**
	 * Parses the Cache-Control header and returning an array representation of the Cache-Control
	 * header.
	 *
	 *     // Create the cache control header
	 *     $response->headers('cache-control', 'max-age=3600, must-revalidate, public');
	 *
	 *     // Parse the cache control header
	 *     if ($cache_control = Rest_HTTP_Header::parse_cache_control($response->headers('cache-control')))
	 *     {
	 *          // Cache-Control header was found
	 *          $maxage = $cache_control['max-age'];
	 *     }
	 *
	 * @param   array   $cache_control Array of headers
	 * @return  mixed
	 */
	public static function parse_cache_control($cache_control)
	{
		$directives = explode(',', strtolower($cache_control));

		if ($directives === FALSE)
		{
			return FALSE;
		}

		$output = array();

		foreach ($directives as $directive)
		{
			if (strpos($directive, '=') !== FALSE)
			{
				list($key, $value) = explode('=', trim($directive), 2);

				$output[$key] = ctype_digit($value) ? (int) $value : $value;
			}
			else
			{
				$output[] = trim($directive);
			}
		}

		return $output;
	}

} // End Rest_HTTP_Header