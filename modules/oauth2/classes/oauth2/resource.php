<?php defined('SYS_PATH') or die('No direct script access.');

class Oauth2_Resource extends Rest_Resource {

	/**
	 * @var int
	 */
	protected static $_user_id=0;

	/**
	 * @var int
	 */
	protected static $_client_id=0;

	/**
	 * @var OAuth2_Storage
	 */
	protected static $_oauth2_storage;

	/**
	 * @var OAuth2
	 */
	protected static $_oauth2;

	/**
	 * @var array
	 */
	protected static $_access_token;

	/**
	 * 进行Oauth2认证
	 */
	public function before($skip_verify = FALSE)
	{
		parent::before();
		if(!$skip_verify)
		{
			self::verify();
		}
	}

	public function verify()
	{
		if (is_null(self::$_oauth2))
		{
			self::$_oauth2_storage = OAuth2_Storage::instance();
			self::$_oauth2 = OAuth2::instance(self::$_oauth2_storage);
			$token = self::$_oauth2->getBearerToken();

			$resource = $this->request->get_resource_name();
			$scope = strtolower($this->request->method) . '_' . $resource;
			//检查是否单独配置权限，未配置默认为资源名
			$permissions = Rest_Config::get('scopes.permission');
			$scope = isset($permissions[$scope])
				? $permissions[$scope]
				: (isset($permissions[$resource]) ? $permissions[$resource]
					: $permissions['default']
				);
			self::$_access_token = self::$_oauth2->verifyAccessToken($token, $scope);
			self::$_user_id = (int) self::$_access_token['user_id'];
			self::$_client_id = (int) self::$_access_token['client_id'];
		}
		else
		{
			$resource = $this->request->get_resource_name();
			$scope = strtolower($this->request->method) . '_' . $resource;
			//检查是否单独配置权限，未配置默认为资源名
			$permissions = Rest_Config::get('scopes.permission');
			$scope = isset($permissions[$scope])
				? $permissions[$scope]
				: (isset($permissions[$resource]) ? $permissions[$resource]
					: $permissions['default']
				);
			self::$_oauth2->verifyScope($scope, self::$_access_token);
		}
	}

	/**
	 * 获取用户ID
	 * @return int
	 */
	public static function get_user_id()
	{
		return self::$_user_id;
	}

	/**
	 * 获取客户端ID
	 * @return string
	 */
	public static function get_client_id()
	{
		return self::$_client_id;
	}
	
	public static function get_scope_list()
	{
		static $scope_list;
		if(is_null($scope_list))
		{
			if(self::$_access_token)
			{
				$scope_list = explode(' ',trim(self::$_access_token['scope']));
			}
			else 
			{
				$scope_list = array();
			}
		}
		return $scope_list;
	}

	/**
	 * 获取客户端类型
	 * @return int
	 */
	public static function get_client_type()
	{
		static $client_type;
		if (is_null($client_type))
		{
			if(self::$_oauth2 instanceof OAuth2) {
				$client = self::$_oauth2_storage->getClientDetails(self::$_client_id);
				if($client)
				{
					$client_type = $client['client_type'];
				}
			}
		}
		return $client_type;
	}

	/**
	 * 获取oauth2资源日志
	 * @return array
	 */
	public static function get_resource_log()
	{
		static $log;
		if (empty($log))
		{
			$log = array_merge(parent::get_resource_log(),
				array(
				     'client_id'   => Oauth2_Resource::get_client_id(),
				     'uid'         => Oauth2_Resource::get_user_id(),
				));

		}
		return $log;
	}
}
