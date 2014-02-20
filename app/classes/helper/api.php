<?php defined('SYS_PATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * API辅助文件
 */
class Helper_Api {

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

	/*
	 * 截取固定长度限UTF-8
	 */
	public static function cut_fix_len($str, $len, $suffix = true)
	{
		$tstr = '';
		preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str,
			$match);
		$i = 0;
		foreach ($match[0] as $val)
		{
			$i = ord($val) > 127 ? $i + 2 : $i + 1;
			$tstr .= $val;
			if ($i > $len)
			{
				break;
			}
		}
		if ($tstr != $str && $suffix == true)
		{
			$tstr .= '...';
		}
		return $tstr;
	}

	/**
	 * 字数统计
	 * @param string $str
	 * @return int
	 */
	public static function word_count($str)
	{
		return ceil(strlen(iconv("utf-8", "gbk", $str)) / 2);
	}

	/**
	 *
	 * 构造消息体id
	 */
	public static function uuid()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

} // End api