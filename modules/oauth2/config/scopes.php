<?php defined('SYS_PATH') or die('No direct script access.');

/**
 * 权限映射
 * 请求方法_list_请求资源：请求列表操作
 * 请求方法_请求资源: 请求单个资源操作
 * 请求资源 ：请求所有资源资源
 */
return array
(
	'permission' => array(

		'reports'=>'backend',
		'default' => 'basic', //默认建议设置为基本权限，如果设置为NULL，不验证权限域scope，
	),
	'name' => array(
		'basic' => '基本信息',
		'backend' => '后台管理'
	)
);
