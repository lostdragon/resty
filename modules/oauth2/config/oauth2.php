<?php defined('SYS_PATH') or die('No direct script access.');

/**
 * oauth2权限配置文件
 */
return array
(
	// The lifetime of access token in seconds.default: 3600
	'access_token_lifetime' => '2592000',
	'access_token_lifetime_by_level' => array(86400,604800,2592000,7776000,15552000,31536000,86400000),
	// The lifetime of refresh token in seconds.default: 1209600
	'refresh_token_lifetime' => '1209600',
	// The lifetime of auth code in seconds.default: 30 建议不超过600秒
	'auth_code_lifetime' => '600',
	// Array of scopes you want to support
	'supported_scopes' => array(
		'basic', 'contacts', 'users'
	),
	// Token type to respond with. Currently only "bearer" supported.
	'token_type' => 'Bearer',
	//HTTP 认证头
	'realm' => 'Service',
	// Set to true to enforce redirect_uri on input for both authorize and token steps.
	'enforce_redirect' => '',
	// Set to true to enforce state to be passed in authorization (see http://tools.ietf.org/html/draft-ietf-oauth-v2-21#section-10.12)
	'enforce_state' => FALSE,

	'salt' => 'salt4hash!',

	'client_id_regexp' => '/^[0-9]{1,11}$/i'
);

