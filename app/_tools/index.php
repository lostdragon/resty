<?php
require 'config.php';
session_start();

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . 'callback.php';
$state = md5(uniqid(rand(), TRUE));
?>
<html>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>MOMO API V4 接口测试</title>
</head>
<body>

<div id="container">
    <div id="wrapper">
        <div id="content">
            <a href="<?php echo API_LOGIN; ?>?redirect_uri=<?php echo $redirect_uri;?>&response_type=<?php echo RESPONSE_TYPE;?>&client_id=<?php echo CLIENT_ID;?>&scope=<?php echo SCOPE;?>&state=<?php echo $state;?>">oauth2登陆</a>
            <a href="api.php">直接进入接口测试</a>
            <a href="../oauth2/developer">开发者信息</a>
            <a href="../oauth2/client_list">应用列表</a>
			<?php
			if (! empty($_SESSION['user_id'])):
				echo '<a href="../oauth2/logout">退出登陆</a>';
			endif;
			?>
        </div>
    </div>
</div>
</body>
</html>