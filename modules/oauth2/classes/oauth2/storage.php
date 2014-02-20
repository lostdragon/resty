<?php

/**
 * @file
 * Sample OAuth2 Library PDO DB Implementation.
 *
 */

/**
 * PDO storage engine for the OAuth2 Library.
 *
 * IMPORTANT: This is provided as an example only. In production you may implement
 * a client-specific salt in the OAuth2_Storage::hash() and possibly other goodies.
 *
 *** The point is, use this as an EXAMPLE ONLY. ***
 */
class OAuth2_Storage implements OAuth2_Interface_Grant_Code, OAuth2_Interface_RefreshTokens, OAuth2_Interface_Grant_User {

	/**@#+
	 * Centralized table names
	 *
	 * @var string
	 */
	const DATABASE_CONFIG = 'oauth2';
	const TABLE_CLIENTS = 'clients';
	const TABLE_CODES = 'auth_codes';
	const TABLE_TOKENS = 'access_tokens';
	const TABLE_REFRESH = 'refresh_tokens';
	/**@#-*/

	/**
	 * @var Rest_Database
	 */
	private $db;

	/**
	 * @var Rest_Redis
	 */
	private $cache;

	/**
	 * 实例
	 * @var OAuth2_Storage
	 */
	protected static $instance;

	/**
	 * 单例模式
	 * @param Rest_Database $db
	 * @param Rest_Redis $cache
	 * @return OAuth2_Storage 返回实例对象
	 */
	public static function instance(Rest_Database $db = NULL, Rest_Redis $cache = NULL)
	{
		if (! isset(OAuth2_Storage::$instance))
		{
			// Create a new instance
			OAuth2_Storage::$instance = new OAuth2_Storage($db, $cache);
		}
		return OAuth2_Storage::$instance;
	}

	/**
	 * Implements OAuth2::__construct().
	 */
	public function __construct(Rest_Database $db = NULL, Rest_Redis $cache = NULL)
	{
		if ($db instanceof Rest_Database)
		{
			$this->db = $db;
		}
		else
		{
			$this->db = Rest_Database::instance(self::DATABASE_CONFIG);
		}

		if ($cache instanceof Rest_Redis)
		{
			$this->cache = $cache;
		}
		else
		{
			$this->cache = Rest_Redis::instance();
		}

	}

	/**
	 * Implements OAuth2_Interface_Storage::checkClientCredentials().
	 *
	 */
	public function checkClientCredentials($client_id, $client_secret = NULL)
	{
		$client = $this->getClientDetails($client_id);
		if ($client)
		{
			return $this->checkPassword($client_secret, $client['client_secret'], $client_id);
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Implements OAuth2_Interface_Storage::getClientDetails().
	 */
	public function getClientDetails($client_id)
	{
		$key = 'oc_' . $client_id;
		if (! $result = $this->cache->get($key))
		{
			$sql = 'SELECT * FROM ' . self::TABLE_CLIENTS . ' WHERE client_id = :client_id';
			$result = $this->db->query_row_assoc($sql, array(':client_id' => $client_id));
			$this->cache->setex($key, 86400, $result);
		}
		return $result;
	}
	
	public function getClientDetailsByKey($client_key)
	{
		$sql = 'SELECT * FROM ' . self::TABLE_CLIENTS . ' WHERE client_key = :client_key';
		$result = $this->db->query_row_assoc($sql, array(':client_key' => $client_key));
		return $result;
	}

	public function getClientList($user_id)
	{
		$sql = 'SELECT * FROM ' . self::TABLE_CLIENTS . ' WHERE owner_id = :owner_id';
		$result = $this->db->query_all_assoc($sql, array(':owner_id' => $user_id));
		return $result;
	}

	/**
	 * Implements OAuth2_Interface_Storage::getAccessToken().
	 */
	public function getAccessToken($oauth_token)
	{
		return $this->getToken($oauth_token, FALSE);
	}

	/**
	 * Implements OAuth2_Interface_Storage::setAccessToken().
	 */
	public function setAccessToken($oauth_token, $client_id, $user_id, $expires, $scope = NULL)
	{
		$this->setToken($oauth_token, $client_id, $user_id, $expires, $scope, FALSE);
	}

	/**
	 * @see IOAuth2Storage::getRefreshToken()
	 */
	public function getRefreshToken($refresh_token)
	{
		return $this->getToken($refresh_token, TRUE);
	}

	/**
	 * @see IOAuth2Storage::setRefreshToken()
	 */
	public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = NULL)
	{
		return $this->setToken($refresh_token, $client_id, $user_id, $expires, $scope, TRUE);
	}

	/**
	 * @see IOAuth2Storage::unsetRefreshToken()
	 */
	public function unsetRefreshToken($refresh_token)
	{
		$this->db->begin();
		$sql = 'DELETE FROM ' . self::TABLE_REFRESH . ' WHERE refresh_token = :refresh_token';
		$this->db->query($sql, array(':refresh_token' => $refresh_token));
		$this->db->commit();
	}

	/**
	 * Implements OAuth2_Interface_Storage::getAuthCode().
	 */
	public function getAuthCode($code)
	{
		$sql = 'SELECT code, client_id, user_id, redirect_uri, expires, scope FROM ' . self::TABLE_CODES .
			' WHERE code = :code';
		$result = $this->db->query_row_assoc($sql, array(':code' => $code));

		return $result !== FALSE ? $result : NULL;

	}

	/**
	 * Implements OAuth2_Interface_Storage::setAuthCode().
	 *
	 * @param string $code
	 * @param string $client_id
	 * @param int $user_id
	 * @param string $redirect_uri
	 * @param int $expires
	 * @param string $scope
	 */
	public function setAuthCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = NULL)
	{

		$sql = 'INSERT INTO ' . self::TABLE_CODES .
			' (code, client_id, user_id, redirect_uri, expires, scope) VALUES (:code, :client_id, :user_id, :redirect_uri, :expires, :scope)';
		$this->db->begin();
		$this->db->query($sql,
			array(
			     ':code'         => $code,
			     ':client_id'    => $client_id,
			     ':user_id'      => $user_id,
			     ':redirect_uri' => $redirect_uri,
			     ':expires'      => $expires,
			     ':scope'        => $scope,
			));
		$this->db->commit();

	}

	/**
	 * @see IOAuth2Storage::checkRestrictedGrantType()
	 */
	public function checkRestrictedGrantType($client_id, $grant_type)
	{
		return TRUE; // Not implemented
	}

	/**
	 * Creates a refresh or access token
	 *
	 * @param string $token - Access or refresh token id
	 * @param string $client_id
	 * @param int $user_id
	 * @param int $expires
	 * @param string $scope
	 * @param bool $isRefresh
	 * @return bool
	 */
	protected function setToken($token, $client_id, $user_id, $expires, $scope, $isRefresh = TRUE)
	{

		$tableName = $isRefresh ? self::TABLE_REFRESH : self::TABLE_TOKENS;
		$tokenName = $isRefresh ? "refresh_token" : "access_token";
		$sql = "INSERT INTO $tableName ($tokenName, client_id, user_id, expires, scope)
				VALUES (:token, :client_id, :user_id, :expires, :scope)";
		$this->db->begin();
		$this->db->query($sql,
			array(
			     ':token'     	=> $token,
			     ':client_id' 	=> $client_id,
			     ':user_id'   	=> $user_id,
			     ':expires'   	=> $expires,
			     ':scope'     	=> $scope,
			));
		return $this->db->commit();
	}

	/**
	 * Retrieves an access or refresh token.
	 *
	 * @param string $token
	 * @param bool $isRefresh
	 * @return array|null
	 */
	protected function getToken($token, $isRefresh = true)
	{
		$key = 'ot_' . (int) $isRefresh . '_' . $token;
		if (! $result = $this->cache->get($key))
		{
			$tableName = $isRefresh ? self::TABLE_REFRESH : self::TABLE_TOKENS;
			$tokenName = "access_token";
			$sql = "SELECT $tokenName, client_id, expires, scope, user_id FROM $tableName WHERE $tokenName = :token";

			$result = $this->db->query_row_assoc($sql, array(':token' => $token));

			$this->cache->setex($key, 86400, $result);
		}
		return $result !== FALSE ? $result : NULL;
	}

	/**
	 * Change/override this to whatever your own password hashing method is.
	 *
	 * In production you might want to a client-specific salt to this function.
	 *
	 * @param string $client_secret
	 * @param string $client_id
	 * @return string
	 */
	protected function hash($client_secret, $client_id)
	{
		return hash('sha1', $client_id . $client_secret . Rest_Config::get('oauth2.salt'));
	}

	/**
	 * Checks the password.
	 * Override this if you need to
	 *
	 * @param string $try
	 * @param string $client_secret
	 * @param string $client_id
	 * @return bool
	 */
	protected function checkPassword($try, $client_secret, $client_id)
	{
//		return $client_secret == $this->hash($try, $client_id);
		return $client_secret == $try;
	}

	/**
	 * @param $client_id
	 * @param $username
	 * @param $password
	 * @return array|bool
	 */
	public function checkUserCredentials($client_id, $username, $password)
	{
//		$user = Model_User::instance();
//		/** @var Model_User $user */
//		return $user->verify($username, $password);
	}

	/**
	 * Insert/update a new client with this server (we will be the server)
	 * When this is a new client, then also generate the client id and secret.
	 * Never updates the client id and secret.
	 * When the client_id is set, then the client_secret must correspond to the entry
	 * being updated.
	 *
	 * (This is the registry at the server, registering clients ;-) )
	 *
	 * @param array $client
	 * @param int $user_id    user registering this consumer
	 * @param bool $user_is_admin
	 * @return string
	 * @throws Rest_Exception
	 */
	public function updateClient($client, $user_id, $user_is_admin = false)
	{
		if (! empty($client['client_id']))
		{
			if (! $user_is_admin && empty($client['client_secret']))
			{
				throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST, 'The field "client_secret" must be set and non empty');
			}
			$this->db->begin();
			// Check if the current user can update this server definition
			if (! $user_is_admin)
			{
				$client_info = $this->getClientDetails($client['client_id']);

				if ($client_info['owner_id'] != $user_id)
				{
					throw new Rest_Exception(Rest_Response::HTTP_BAD_REQUEST,
						'The user "' . $user_id . '" is not allowed to update this consumer');
				}
			}
			else
			{
				// User is an admin, allow a key owner to be changed or key to be shared
				if (array_key_exists('user_id', $client))
				{
					if (is_null($client['user_id']))
					{
						$this->db->query('
							UPDATE ' . self::TABLE_CLIENTS . '
							SET owner_id = NULL
							WHERE client_id = :client_id
							', array(':client_id' => $client['client_id']));
					}
					else
					{
						$this->db->query('
							UPDATE ' . self::TABLE_CLIENTS . '
							SET owner_id = :owner_id
							WHERE client_id = :client_id
							', array(':owner_id' => $client['user_id'], ':client_id' => $client['client_id']));
					}
				}
			}

			$this->db->query('
				UPDATE ' . self::TABLE_CLIENTS . '
				SET redirect_uri		= :redirect_uri,
					client_uri		= :client_uri,
					client_name	= :client_name,
					client_desc	= :client_desc,
					client_logo	= :client_logo,
					client_type	= :client_type,
					timestamp			= NOW()
				WHERE client_id    = :client_id
				  AND client_secret = :client_secret
				',
				array(
				     ':redirect_uri'   => isset($client['redirect_uri']) ? $client['redirect_uri'] : '',
				     ':client_uri'     => isset($client['client_uri']) ? $client['client_uri'] : '',
				     ':client_name'    => isset($client['client_name']) ? $client['client_name'] : '',
				     ':client_desc'    => isset($client['client_desc']) ? $client['client_desc'] : '',
				     ':client_logo'    => isset($client['client_logo']) ? $client['client_logo'] : '',
				     ':client_type'    => isset($client['client_type']) ? $client['client_type'] : '',
				     ':client_id'      => $client['client_id'],
				     ':client_secret'  => $client['client_secret']
				)
			);
			$this->db->commit();
			$client_id = $client['client_id'];
			$this->cache->delete('oc_' . $client_id);
		}
		else
		{
			$client_secret = $this->generateKey();
			// When the user is an admin, then the user can be forced to something else that the user
			if ($user_is_admin && array_key_exists('user_id', $client))
			{
				if (is_null($client['user_id']))
				{
					$owner_id = 'NULL';
				}
				else
				{
					$owner_id = (int) $client['user_id'];
				}
			}
			else
			{
				// No admin, take the user id as the owner id.
				$owner_id = (int) $user_id;
			}
			$this->db->begin();
			$sql = 'INSERT INTO ' . self::TABLE_CLIENTS .
				' (client_secret, redirect_uri, timestamp, owner_id,
				 client_type, client_uri, client_name, client_desc, client_logo)
			     VALUES (:client_secret, :redirect_uri, NOW(), :owner_id,
			     :client_type, :client_uri, :client_name, :client_desc, :client_logo)';
			$result = $this->db->query($sql,
				array(
				     ':client_secret' => $client_secret,
				     ':redirect_uri'  => isset($client['redirect_uri']) ? $client['redirect_uri'] : '',
				     ':owner_id'      => $owner_id,
				     ':client_type'   => isset($client['client_type']) ? $client['client_type'] : '',
				     ':client_uri'    => isset($client['client_uri']) ? $client['client_uri'] : '',
				     ':client_name'   => isset($client['client_name']) ? $client['client_name'] : '',
				     ':client_desc'   => isset($client['client_desc']) ? $client['client_desc'] : '',
				     ':client_logo'   => isset($client['client_logo']) ? $client['client_logo'] : ''
				)
			);
			$this->db->commit();
			$client_id = $result['0'];
		}
		return $client_id;
	}

	/**
	 * Insert/update developer
	 *
	 * @param array $developer
	 * @param int $user_id
	 * @param bool $user_is_admin
	 * @return string
	 * @throws Rest_Exception
	 */
	public function updateDeveloper($developer, $user_id, $user_is_admin = false)
	{

		$result = $this->db->query_one("SELECT COUNT(1) FROM developers WHERE user_id = :user_id",
			array(
			     ':user_id' => $user_id
			));
		$this->db->begin();
		if ($result)
		{
			$this->db->query('
				UPDATE developers
				SET `dev_type`		= :dev_type,
					`dev_name`	= :dev_name,
					`dev_desc`	= :dev_desc,
					`dev_email`	= :dev_email,
					`dev_tel`	= :dev_tel,
					`timestamp`			= NOW()
				WHERE user_id    = :user_id',
				array(
				     ':dev_type'           => isset($developer['dev_type']) ? $developer['dev_type'] : '',
				     ':dev_name'           => isset($developer['dev_name']) ? $developer['dev_name'] : '',
				     ':dev_desc'           => isset($developer['dev_desc']) ? $developer['dev_desc'] : '',
				     ':dev_email'          => isset($developer['dev_email']) ? $developer['dev_email'] : '',
				     ':dev_tel'            => isset($developer['dev_tel']) ? $developer['dev_tel'] : '',
				     ':user_id'            => $user_id,
				)
			);
		}
		else
		{
			$sql = 'INSERT INTO developers (user_id, dev_type, dev_name, dev_desc, dev_email, dev_tel, timestamp)
			     VALUES (:user_id, :dev_type,  :dev_name, :dev_desc, :dev_email, :dev_tel, NOW())';
			$this->db->query($sql,
				array(
				     ':user_id'     => $user_id,
				     ':dev_type'    => isset($developer['dev_type']) ? $developer['dev_type'] : '',
				     ':dev_name'    => isset($developer['dev_name']) ? $developer['dev_name'] : '',
				     ':dev_desc'    => isset($developer['dev_desc']) ? $developer['dev_desc'] : '',
				     ':dev_email'   => isset($developer['dev_email']) ? $developer['dev_email'] : '',
				     ':dev_tel'     => isset($developer['dev_tel']) ? $developer['dev_tel'] : '',
				)
			);
		}
		return $this->db->commit();
	}

	/**
	 *
	 * @param $user_id
	 * @return array|mixed
	 */
	public function getDeveloperDetails($user_id)
	{
		$sql = 'SELECT * FROM developers WHERE user_id = :user_id';
		$result = $this->db->query_row_assoc($sql, array(':user_id' => $user_id));
		return $result;
	}

	/**
	 * Generate a unique key
	 *
	 * @param boolean $unique    force the key to be unique
	 * @return string
	 */
	public function generateKey($unique = false)
	{
		$key = md5(uniqid(rand(), true));
		if ($unique)
		{
			list($usec, $sec) = explode(' ', microtime());
			$key .= dechex($usec) . dechex($sec);
		}
		return $key;
	}
}
