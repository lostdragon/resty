<?php defined('SYS_PATH') or die('No direct access allowed.');
/**
 * oauth2 数据库配置文件
 */
return array(
	'default' => array
	(
		'type' => 'mysqli', //default,now only support mysqli
		'master' => array(
			array(
				/**
				 * The following options are available for MySQL:
				 *
				 * string   hostname     server hostname, or socket
				 * string   database     database name
				 * string   username     database username
				 * string   password     database password
				 * boolean  persistent   use persistent connections?
				 * int      port         database port
				 * string   socket       database socket
				 * array    variables    system variables as "key => value" pairs
				 */
				'hostname' => '127.0.0.1',
				'database' => 'oauth2',
                'username' => 'test',
                'password' => 'test',
				'persistent' => FALSE,
//				'port' => 3306,
//				'socket' => '/var/lib/mysql/mysql.sock',
			)
		),
		'table_prefix' => '',
		'charset' => 'utf8',
	)
);