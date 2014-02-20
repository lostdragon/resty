<?php
/**
 * MOMO API 测试接口中转
 *
 */
session_start();

include_once ('config.php');
$access_token = trim($_REQUEST['access_token']);

if (empty($access_token))
{
	$response = json_decode($_SESSION['response'], TRUE);
	$access_token = isset($response['access_token']) ? $response['access_token'] : '';
}
if(empty($access_token)) {
	$access_token = isset($_COOKIE['access_token']) ? $_COOKIE['access_token'] : '';
}

error_reporting(0);
$method = trim($_REQUEST['method']);
$rtype = strtolower($_REQUEST['rtype']);
$reqtype = trim($_REQUEST['reqtype']);
$reqbody = trim($_REQUEST['reqbody']);

$_SESSION['method'] = $method;
$_SESSION['rtype'] = $_REQUEST['rtype'];
$_SESSION['reqtype'] = $reqtype;
$_SESSION['reqbody'] = $reqbody;

$query = array('access_token' => $access_token);
switch (strtoupper($reqtype))
{
	case 'GET':
	case 'DELETE':
		$urls = explode('?', $method);
		$path = '';
		if (! empty($urls))
		{
			$path = isset($urls['0']) ? $urls['0'] : '';
			$array = isset($urls['1']) ? explode('&', $urls['1']) : array();
			if (! empty($array))
			{
				foreach ($array as $value)
				{
					if ($value)
					{
						list ($k, $v) = explode('=', $value);
						$query[$k] = $v;
					}
				}
			}
		}
		$result = http(API_PATH . $method, $reqtype, $query);
		break;
	case 'POST':
	case 'PUT':
		$result = http(API_PATH . $method, $reqtype, $query, $reqbody, 'json');
		break;


}

$result['body'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $result['body']);

if ($rtype == 'php') {
	$result['body'] = print_r(json_decode($result['body'], TRUE), TRUE);
}
echo json_encode($result);