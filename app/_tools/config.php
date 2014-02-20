<?php
define('PRODUCTION', 10);
define('STAGING', 20);
define('TESTING', 30);
define('DEVELOPMENT', 40);

define('ENVIRONMENT', DEVELOPMENT);
define("CLIENT_ID", 1);
define("CLIENT_KEY", '5a6161c61488f680bf75ccfb4a6e3376');
define("RESPONSE_TYPE", 'code');
define("SCOPE", 'basic');
//根据环境配置
switch (ENVIRONMENT)
{
	case PRODUCTION:
		define('API_PATH', 'http://api.91.com/');
		define('API_LOGIN', 'http://resty.91.com/oauth2/login');
		define('API_TOKEN', 'http://resty.91.com/oauth2/token');
		break;
	case STAGING:
		define('API_PATH', 'http://api.91.com/');
		define('API_LOGIN', 'http://resty.91.com/oauth2/login');
		define('API_TOKEN', 'http://resty.91.com/oauth2/token');
		break;
	case TESTING:
		define('API_PATH', 'http://api.91.com/');
		define('API_LOGIN', 'http://resty.91.com/oauth2/login');
		define('API_TOKEN', 'http://resty.91.com/oauth2/token');
		break;
	case DEVELOPMENT:
		define('API_PATH', 'http://resty.91.com/');
		define('API_LOGIN', 'http://resty.91.com/oauth2/login');
		define('API_TOKEN', 'http://resty.91.com/oauth2/token');
		break;
}


function http($url, $method = 'GET', $get = NULL, $post = NULL, $content_type = 'form')
{
	if(is_array($get)) {
		$url = $url.'?'.http_build_query($get);
	}
	$ci = curl_init();
	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ci, CURLOPT_TIMEOUT, 10);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);

	curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method);
//	curl_setopt($ci, CURLOPT_PUT, 1);

	$header = array();

	if (! empty($post))
	{

		curl_setopt($ci, CURLOPT_POST, TRUE);

		curl_setopt($ci, CURLOPT_POSTFIELDS, $post);
		if ($content_type == 'json')
		{
			$header = array("Content-Type: application/json");
		}
		else
		{
			$header = array("Content-Type: application/x-www-form-urlencoded");
		}
		array_push($header, 'Expect:');
	}

	/**
	 * 在使用curl做POST的时候, 当要POST的数据大于1024字节的时候, curl并不会直接就发起POST请求, 而是会分为俩步
	 * 1. 发送一个请求, 包含一个Expect:100-continue, 询问Server使用愿意接受数据
	 * 2. 接收到Server返回的100-continue应答以后, 才把数据POST给Server
	 * 取消Expect:100-continue的请求
	 */


	curl_setopt($ci, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);

	curl_setopt($ci, CURLOPT_URL, $url);
	$res['body'] = curl_exec($ci);
	$res['code'] = curl_getinfo($ci, CURLINFO_HTTP_CODE);
	curl_close($ci);
	return $res;
}

/**
 * parses the url and rebuilds it to be
 * scheme://host/path
 */
function get_normalized_http_url($url) {
	$parts = parse_url($$url);

	$port = @$parts['port'];
	$scheme = $parts['scheme'];
	$host = $parts['host'];
	$path = @$parts['path'];

	$port or $port = ($scheme == 'https') ? '443' : '80';

	if (($scheme == 'https' && $port != '443')
		|| ($scheme == 'http' && $port != '80')) {
		$host = "$host:$port";
	}
	return "$scheme://$host$path";
}