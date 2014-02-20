<?php defined('SYS_PATH') or die('No direct script access.');

class Resource_Oauth2 extends Rest_Resource {

	public function before()
	{
		parent::before();
		session_start();
	}

	public function after()
	{
		exit(1);
	}

	//开发者信息
	public function developer()
	{
		if (! $this->_is_login())
		{
			header('Location: ' . Rest_URL::site('oauth2/login'));
			exit(1);
		}
		$user_id = $_SESSION['user_id'];
		$oauth_storage = OAuth2_Storage::instance();
		//开发者类型：个人、公司

		//开发者来源：开发者、内容提供商、作者？

		//所在地？

		//开发者姓名

		//开发者简介

		//邮箱

		//电话

		//聊天工具

		//开发者官方网站

		//LOGO

		//接受站内消息？

		$developer = $oauth_storage->getDeveloperDetails($user_id);
		if ($_POST)
		{
			$post = $_POST;
			$result = $oauth_storage->updateDeveloper($post, $user_id);
			if ($result)
			{
				echo '修改成功！';
			}
			$developer = $oauth_storage->getDeveloperDetails($user_id);
		}

		include Rest::find_file('developer', 'views');
	}

	public function client_list()
	{
		header('Content-Type: text/html; charset=utf-8');
		if (! $this->_is_login())
		{
			header('Location: ' . Rest_URL::site('oauth2/login'));
			exit(1);
		}
		$oauth_storage = OAuth2_Storage::instance();
		$user_id = $_SESSION['user_id'];
		if (! $oauth_storage->getDeveloperDetails($user_id))
		{
			echo '您不是开发者，必须先<a href="'.Rest_URL::site('oauth2/developer').'">注册开发者</a>';
			exit();
		}
		$list = $oauth_storage->getClientList($user_id);
		echo '<a href="../_tools">返回</a> <a href="'. Rest_URL::site('oauth2/client').'">应用注册</a>';

		echo '<table border="1" cellpadding="0" cellspacing="0">';
		foreach ($list as $i => $client)
		{
			if ($i == 0)
			{
				echo '<tr>';
				foreach ($client as $key => $val)
				{
					if (in_array($key, array('client_id', 'client_type', 'client_name')))
					{
						echo "<td>$key</td>";
					}
				}
				echo '<tr/>';
			}
			echo '<tr>';
			foreach ($client as $key => $val)
			{
				if ($key == 'client_id')
				{
					echo '<td><a href='. Rest_URL::site('oauth2/client?client_id='.$val).'>'.$val.'</a></td>';
				}
				elseif (in_array($key, array('client_id', 'client_type', 'client_name')))
				{
					echo "<td>$val</td>";
				}
			}
			echo '<tr/>';
		}

		echo '</table>';
		exit(1);
	}

	//创建应用
	public function client()
	{
		if (! $this->_is_login())
		{
			header('Location: ' . Rest_URL::site('oauth2/login'));
			exit(1);
		}
		$oauth_storage = OAuth2_Storage::instance();
		if (! $oauth_storage->getDeveloperDetails($_SESSION['user_id']))
		{
			echo '您不是开发者，必须先<a href="'.Rest_URL::site('oauth2/developer').'">注册开发者</a>';
			exit();
		}

		if (isset($_GET['client_id']))
		{
			$client = $oauth_storage->getClientDetails($_GET['client_id']);
		}
		else
		{
			$client = array();
		}
		if ($_POST)
		{
			//android
//			$package_name = 'com.example';
//			$sign = 'sha1';

			//ios
//			$bundle_id = '';
//			$app_store_id = '';

			//service/other
                //client_id\client_secret由服务端生成
            $post = $_POST;
            $post['client_secret'] = isset($client['client_secret']) ? $client['client_secret'] : '';
            $client_id = $oauth_storage->updateClient($post, $_SESSION['user_id']);
            $client = $oauth_storage->getClientDetails($client_id);
			echo (isset($_GET['client_id']) ? '修改' : '创建') . '成功！';
		}
		include Rest::find_file('client', 'views');
	}

	//0, 浏览器客户端请求(测试用)
	public function request()
	{
		extract(
			array(
			     'client_id'     => '2',
			     'redirect_uri'  => 'http://resty.91.com/oauth2/receive',
			     'response_type' => 'token',
			     'scope'         => 'basic+contacts',
			     'state'         => md5(uniqid(rand(), TRUE)),
			));
		include Rest::find_file('request', 'views');
	}

	//0, 服务端客户端请求(测试用)
	public function request_code()
	{
		extract(
			array(
			     'client_id'     => '1',
			     'redirect_uri'  => 'http://resty.91.com/oauth2/receive',
			     'response_type' => 'code',
			     'scope'         => 'users',
			     'state'         => md5(uniqid(rand(), TRUE)),
			));
		include Rest::find_file('request', 'views');
	}

	//0, 服务端客户端响应(测试用)
	public function receive()
	{
//		grant_type=authorization_code&client_id=0123456789ab&client_secret=hello world&code=DR2BYUbpLAEahiwQZ7BrOVD8Ot09MWMl&redirect_uri=/
		extract(
			array(
			     'grant_type'    => 'authorization_code',
			     'client_id'     => '1',
			     'client_secret' => '63f20943a1fceef96c9509cad5d66602',
			     'code'          => isset($_GET['code']) ? $_GET['code'] : '',
			     'redirect_uri'  => 'http://resty.91.com/oauth2/receive',
			     'state'         => md5(uniqid(rand(), TRUE)),
			));
		include Rest::find_file('receive', 'views');
	}

	//1.认证
	public function login()
	{
		$view = 'login';

		$request_uri = $_SERVER['REQUEST_URI'];


		if (isset($_REQUEST['client_id']))
		{
			$request_uri = str_replace(__FUNCTION__, 'authorize', $request_uri);
		}
		else
		{
			$request_uri = str_replace(__FUNCTION__, '../_tools', $request_uri);
		}

		if ($this->_is_login())
		{
			header('Location: ' . $request_uri);
			exit(1);
		}
		if (isset($_REQUEST['client_id']))
		{
			$oauth_storage = OAuth2_Storage::instance();
			$client = $oauth_storage->getClientDetails($_REQUEST['client_id']);
			if ($client)
			{
				$title = '用户登陆';
				$app_title = $client['client_name'];
				$error = '';

				//根据应用类型或请求参数可以加载不同的视图
				if ($client['client_type'] != 0 OR $_REQUEST['view'] == 'wap')
				{
					$view = 'wap-login';
				}
			}
			else
			{

				echo '应用不存在';
				exit(1);
			}
		}
		else
		{
			$title = '用户登陆';
			$app_title = '';
			$error = '';
		}
		if ($_POST)
		{
			$account = trim($_POST ['account']);
			$password = trim($_POST ['password']);
			if (empty ($account))
			{
				$error = '账号为空';
			}
			elseif (empty ($password))
			{
				$error = '密码为空';
			}
			else
			{
				if ($account = 'admin' && $password == 'admin')
				{
					$_SESSION['user_id'] = 1;
					header('Location: ' . $request_uri);
					exit();
				}
				else
				{
					$error = '帐号和密码不匹配';
				}
			}
		}
		include Rest::find_file($view, 'views');
	}

	//2.授权
	public function authorize()
	{
		if (! $this->_is_login())
		{
			header('Location: ' . Rest_URL::site('oauth2/login'));
			exit(1);
		}
		$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : 0;
		$_GET['scope'] = isset($_GET['scope']) ? $_GET['scope'] : 'basic';

		$oauth_storage = OAuth2_Storage::instance();
		$oauth = OAuth2::instance($oauth_storage);

		if ($_POST)
		{
			$userId = $_SESSION['user_id']; // Use whatever method you have for identifying users.
			$oauth->finishClientAuthorization($_POST["accept"] == "授权", $userId, $_POST);
		}

		$auth_params = $oauth->getAuthorizeParams();

		$client = $oauth_storage->getClientDetails($client_id);
		$client_name = $client['client_name'];

		$scopes = explode(' ', $_GET['scope']);

		$scope_name = '';
		foreach ($scopes as $scope)
		{
			$scope_name .= Rest_Config::get('scopes.name.' . $scope) . ' ';
		}

		include Rest::find_file('authorize', 'views');
	}

	//3.获取token
	public function token()
	{
		$oauth = OAuth2::instance(OAuth2_Storage::instance());
		$oauth->grantAccessToken();
	}

	public function logout()
	{
		$_SESSION['user_id'] = 0;
		header('Location: ' . Rest_URL::site('_tools'));
	}

	private function _is_login()
	{
		if (! empty($_SESSION['user_id']))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

}
