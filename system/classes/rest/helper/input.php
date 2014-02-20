<?php defined('SYS_PATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * API输入辅助文件
 */
class Rest_Helper_Input {

	/**
	 * 获取当前服务器IP
	 * @param string $dest
	 * @param int $port
	 * @return string
	 */
	public static function get_server_ip($dest = '64.0.0.0', $port = 80)
	{
		static $addr;
		if (empty($addr))
		{
			$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			socket_connect($socket, $dest, $port);
			socket_getsockname($socket, $addr, $port);
			socket_close($socket);
		}
		return $addr;
	}

	/**
	 * 获取客户端IP
	 * @return string
	 */
	public static function get_client_ip()
	{
		static $client_ip;
		if (empty($client_ip))
		{
			$client_ip = '0.0.0.0';
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
				AND isset($_SERVER['REMOTE_ADDR'])
					AND in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'localhost', 'localhost.localdomain'))
			)
			{
				// Use the forwarded IP address, typically set when the
				// client is using a proxy server.
				// Format: "X-Forwarded-For: client1, proxy1, proxy2"
				$client_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

				$client_ip = array_shift($client_ips);

				unset($client_ips);
			}
			elseif (isset($_SERVER['HTTP_CLIENT_IP'])
				AND isset($_SERVER['REMOTE_ADDR'])
					AND in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'localhost', 'localhost.localdomain'))
			)
			{
				// Use the forwarded IP address, typically set when the
				// client is using a proxy server.
				$client_ips = explode(',', $_SERVER['HTTP_CLIENT_IP']);

				$client_ip = array_shift($client_ips);

				unset($client_ips);
			}
			elseif (isset($_SERVER['REMOTE_ADDR']))
			{
				// The remote IP address
				$client_ip = $_SERVER['REMOTE_ADDR'];
			}
		}
		return $client_ip;
	}

} // End Rest_Helper_Input