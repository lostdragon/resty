<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

require 'config.php';
if (isset($_GET['error']))
{
	var_dump($_GET);
}
elseif (isset($_GET['code']))
{
	setcookie('response', '');
	$post = array(
		'grant_type' => 'authorization_code',
		'client_id' => CLIENT_ID,
		'client_secret' => CLIENT_KEY,
		'code' => $_GET['code'],
		'redirect_uri' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . 'callback.php'
	);
	$response = http(API_TOKEN, 'POST', NULL, http_build_query($post));

	if ($response['code'] == 200)
	{
		$_SESSION['response'] = $response['body'];
		$data = json_decode($response['body'], TRUE);
		echo '授权完成,<a href="api.php">进入你的API基础测试页面</a>';
		echo '<br/><a href="./chrome?access_token='.$data['access_token'].'">进入你的API高级测试页面(目前仅支持chrome 21版以上)</a>';
	}
	else
	{
		var_dump($response['body']);
	}
}
else
{
	//切换认证方式时清除旧的认证数据
	$_SESSION['response'] = '';
	?>
<script type="text/javascript">
    function getCookie(c_name) {
        if (document.cookie.length > 0) {
            c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1) {
                c_start = c_start + c_name.length + 1;
                c_end = document.cookie.indexOf(";", c_start);
                if (c_end == -1) c_end = document.cookie.length;
                return unescape(document.cookie.substring(c_start, c_end));
            }
        }
        return "";
    }

    function setCookie(c_name, value, expiredays) {
        var exdate = new Date();
        exdate.setDate(exdate.getDate() + expiredays);
        document.cookie = c_name + "=" + escape(value) +
            ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString());
    }

    var params = {}, queryString = location.hash.substring(1),
        regex = /([^&=]+)=([^&]*)/g, m;
    while (m = regex.exec(queryString)) {
        params[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
    }

    setCookie('response', queryString);
    if (params['error']) {
        document.write('认证失败<br/>');
	    for(p in params) {
            document.write(p + ' : ' + params[p] + '<br/>');
	    }
    } else {
        document.write('授权完成,<a href="api.php">进入你的API基础测试页面</a>');
        document.write('<br/><a href="./chrome?access_token=' + params['access_token'] + '">进入你的API高级测试页面(目前仅支持chrome 21版以上)</a>');
    }
</script>
<?php
}
?>