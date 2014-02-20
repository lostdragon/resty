<?php defined('SYS_PATH') or die('No direct script access.');

if (Rest::$environment === Rest::DEVELOPMENT)
{
	return array
	(
		'default' => array(
			'host' => '127.0.0.1',
			'port' => 5672,
			'login' => 'test',
			'password' => 'test',
			'vhost' => '/'
		)
	);
}
elseif (Rest::$environment === Rest::PRODUCTION)
{
	return array
	(
		'default' => array(
			'host' => '10.1.1.1',
			'port' => 5672,
			'login' => 'test',
			'password' => 'test',
			'vhost' => '/'
		)
	);

}