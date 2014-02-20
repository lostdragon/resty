<?php
/**
 * 框架入口文件
 */

//PHP版本必须大于5.3
if (version_compare(PHP_VERSION, '5.3', '<'))
{
	die('PHP Version need >= 5.3');
}

$app_root = dirname(__FILE__);
$doc_root = dirname($app_root);

define('DS', DIRECTORY_SEPARATOR);
define('APP_PATH', $app_root . DS);
define('SYS_PATH', $doc_root . DS . 'system' . DS);
define('MOD_PATH', $doc_root . DS . 'modules' . DS);

unset($app_root, $doc_root);

require SYS_PATH . 'classes' . DS . 'rest.php';
//系统框架初始化
Rest::init(
   array(
//		'base_url' => '/v1', //设置根路径
		'index_file' => ''  //设置主文件
   )
);
//设置开发环境
Rest::$environment = Rest::DEVELOPMENT;
//设置日志保存路径、级别等
//记录错误级别以上日志
Rest::$log->attach(new Rest_Log_File(APP_PATH. 'logs'), Rest_Log::ERROR);
//只记录TRACE日志,用于调试
//Rest::$log->attach(new Rest_Log_Mongo(Rest_Mongo::instance(), 'trace_log'), array(Rest_Log::TRACE));

//引入oauth2等模块
Rest::modules(
	array(
	     'oauth2' => MOD_PATH . 'oauth2',
	)
);
/*获取Request单例，然后执行exec方法，该方法里会调用Route来解析URI获取相应的Resource，
然后实例化Resource，触发相应的HTTP方法，最后返回一个Response对象，Response执行output方法就输出了结果
*/
Rest_Request::instance()->exec()->output();
