<?php defined('SYS_PATH') or die('No direct script access.');

if (Rest::$environment === Rest::DEVELOPMENT)
{
	return array
	(
		'default'=>array(
			'hostname'			=> 'test',
			'port'				=> 6379,
			'timeout'			=> 0,
			'persistent'		=> TRUE,
			'auth'				=>NULL,
			'serializer'		=>Redis::SERIALIZER_PHP, //SERIALIZER_NONE~不串行化，SERIALIZER_PHP~php内置串行匄1�7,SERIALIZER_IGBINARY~igbinary串行匄1�7
			'prefix'			=>NULL,
		),	
	);
}
elseif (Rest::$environment === Rest::PRODUCTION)
{
	return array
	(
		'default'=>array(
			'hostname'			=> '10.1.1.1',
			'port'				=> 6379,
			'timeout'			=> 0,
			'persistent'		=> TRUE,
			'auth'				=>NULL,
			'serializer'		=>Redis::SERIALIZER_PHP, //SERIALIZER_NONE~不串行化，SERIALIZER_PHP~php内置串行匄1�7,SERIALIZER_IGBINARY~igbinary串行匄1�7
			'prefix'			=>NULL,
		),	
	);
}