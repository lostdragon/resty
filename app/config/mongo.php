<?php

return array(

	/**
	 * Configuration Name
	 *
	 * You use this name when initializing a new MongoDB instance
	 *
	 * $db = Rest_Mongo::instance('default');
	 */
	'default' => array(

		/**
		 * Connection Setup
		 * 
		 * See http://www.php.net/manual/en/mongo.construct.php for more information
		 *
		 * or just edit / uncomment the keys below to your requirements
		 */
		'connection' => array(

			/** hostname, separate multiple hosts by commas **/
			'hostname' => '127.0.0.1',

			/** database to connect to **/
			'database'  => 'test',

			/** authentication **/
			//'username'  => 'username',
			//'password'  => 'password',

			/** connection options (see http://www.php.net/manual/en/mongo.construct.php) **/
			//'options'   => array(
				// 'persist'    => 'persist_id',
				// 'timeout'    => 1000, 
				// 'replicaSet' => TRUE
			//)
		),
	)
);