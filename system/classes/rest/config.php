<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Config helper.
 *
 * @package    System
 * @category   Helpers
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
class Rest_Config {
	/**
	 * @var
	 */
	public static $_data;

	/**
	 * 获取配置
	 * @static
	 * @param string $key 配置文件关键字
	 * @param mixed $default 默认值
	 * @return array|NULL
	 */
	public static function get($key, $default = NULL)
	{
		if (isset(self::$_data[$key]))
		{
			$config = self::$_data[$key];
		}
		else
		{
			$items = explode('.', $key, 2);
			$file = implode(DS, array_slice($items, 0, 1));

			if (empty(self::$_data[$file]))
			{

				if ($file = Rest::find_file($file, 'config'))
				{
					self::$_data[$file] = include $file;
				}
				else
				{
					self::$_data[$file] = NULL;
				}
			}
			if (empty($items[1]))
			{
				$config = self::$_data[$file];
			}
			else
			{
				$config = Rest_Arr::path(self::$_data[$file], $items[1]);
			}
		}
		return is_null($config) ? $default : $config;
	}

	/**
	 * 设置配置
	 * @static
	 * @param string $key
	 * @param string $val
	 */
	public static function set($key, $val)
	{
		self::$_data[$key] = $val;
	}
}
