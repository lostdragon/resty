<?php defined('SYS_PATH') or die('No direct script access.');
/**
 * Rest_Model base class. All models should extend this class.
 *
 * @package    System
 * @category   Models
 * @author     Momo Team
 * @copyright  (c) 2009-2012 Momo Team
 */
abstract class Rest_Model {

	/**
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * Create single model instance.(Late Static Binding)
	 * @return static
	 */
	public static function instance()
	{
		$class = get_called_class();
		if (! isset(self::$_instances[$class]))
		{
			self::$_instances[$class] = new $class();
		}
		return self::$_instances[$class];
	}
} // End Rest_Model
