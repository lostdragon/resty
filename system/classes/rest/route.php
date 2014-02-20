<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Route library
 *
 * @package    System
 * @category   Route
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Route_Exception extends Rest_Exception {
}

Class Rest_Route {

	/**
	 * 查找资源是否存在，并路由到相应的资源
	 * @static
	 * @param Rest_Request $request
	 * @return string
	 * @throws Rest_Route_Exception
	 */
	public static function parse(Rest_Request $request)
	{

		$resource_uri = $request->get_uri();
		if ($resource_uri)
		{
			$uris = explode('/', $resource_uri);
			$resources = $resource_id = '';

			for ($i = 0, $count = count($uris); $i < $count; $i += 2)
			{
				if (empty($resources))
				{
					$resources = $uris[$i];
					$resource_id = isset($uris[$i + 1]) ? $uris[$i + 1] : '';
				}
				else
				{
					if (isset($uris[$i - 2]))
					{
						$resource = self::singularize($uris[$i - 2]);
						$request->params($resource . '_id', $resource_id);
					}
					$resources .= '_' . $uris[$i];
					$resource_id = isset($uris[$i + 1]) ? $uris[$i + 1] : '';
				}
			}
			$result = $resources;
			if (is_numeric($resource_id))
			{
				$request->params('id', $resource_id);
			}
			else
			{
				$resource = self::singularize($resources);

				//单复数相同的词认为是动作
				if ($resources == $resource)
				{
					$request->action = $resource_id ? $resource_id : $resources;
				}
				elseif (! empty($resource_id))
				{
					$request->params('id', $resource_id);
				}
				else
				{
					$request->request_type = '_list';
				}
			}
		}
		else
		{
			$result = Rest_Config::get('route.default', '');
		}

		if ($result AND Rest::find_file(strtolower('resource_' . str_replace('/', '_', $result))))
		{
			return $result;
		}
		throw new Rest_Route_Exception(Rest_Response::HTTP_NO_FOUND,
			'resource not found: ' . $resource_uri . ', class ' . $result . ' not exist!');
	}

	/**
	 * Singularizes English nouns.
	 *
	 * @access public
	 * @static
	 * @param    string $word    English noun to singularize
	 * @return string Singular noun.
	 * @see http://www.kavoir.com/2011/04/php-class-converting-plural-to-singular-or-vice-versa-in-english.html
	 */
	public static function singularize($word)
	{
		$singular = array(
			'/(quiz)zes$/i'                                                    => '\1',
			'/(matr)ices$/i'                                                   => '\1ix',
			'/(vert|ind)ices$/i'                                               => '\1ex',
			'/^(ox)en/i'                                                       => '\1',
			'/(alias|status)es$/i'                                             => '\1',
			'/([octop|vir])i$/i'                                               => '\1us',
			'/(cris|ax|test)es$/i'                                             => '\1is',
			'/(shoe)s$/i'                                                      => '\1',
			'/(o)es$/i'                                                        => '\1',
			'/(bus)es$/i'                                                      => '\1',
			'/([m|l])ice$/i'                                                   => '\1ouse',
			'/(x|ch|ss|sh)es$/i'                                               => '\1',
			'/(m)ovies$/i'                                                     => '\1ovie',
			'/(s)eries$/i'                                                     => '\1eries',
			'/([^aeiouy]|qu)ies$/i'                                            => '\1y',
			'/([lr])ves$/i'                                                    => '\1f',
			'/(tive)s$/i'                                                      => '\1',
			'/(hive)s$/i'                                                      => '\1',
			'/([^f])ves$/i'                                                    => '\1fe',
			'/(^analy)ses$/i'                                                  => '\1sis',
			'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
			'/([ti])a$/i'                                                      => '\1um',
			'/(n)ews$/i'                                                       => '\1ews',
			'/s$/i'                                                            => '',
		);

		/*
		// Never have uncountable or irregular word
		$uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

		$irregular = array(
			'person' => 'people',
			'man' => 'men',
			'child' => 'children',
			'sex' => 'sexes',
			'move' => 'moves'
		);

		$lowercased_word = strtolower($word);
		foreach ($uncountable as $_uncountable)
		{
			if (substr($lowercased_word, (- 1 * strlen($_uncountable))) == $_uncountable)
			{
				return $word;
			}
		}

		foreach ($irregular as $_plural => $_singular)
		{
			if (preg_match('/(' . $_singular . ')$/i', $word, $arr))
			{
				return preg_replace('/(' . $_singular . ')$/i', substr($arr[0], 0, 1) . substr($_plural, 1), $word);
			}
		}
		*/
		foreach ($singular as $rule => $replacement)
		{
			if (preg_match($rule, $word))
			{
				return preg_replace($rule, $replacement, $word);
			}
		}

		return $word;
	}
}
