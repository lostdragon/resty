<?php
session_start();
$response = array();
if (! empty($_SESSION['response']))
{
	$response = json_decode($_SESSION['response'], TRUE);
}
elseif (! empty($_COOKIE['response']))
{
	parse_str($_COOKIE['response'], $response);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
<HEAD>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <TITLE> MOMO API 接口测试 </TITLE>
    <style type="text/css">
        body {
            margin: 10px;
            padding: 10px;
            font-size: 14px;
            line-height: 160%
        }

        input {
            width: 280px;
        }

        select {
            width: 280px;
        }

        h1 {
            font-size: 25px;
        }

        h3 {
            font-size: 14px;
        }

        #area {
            width: 100%
        }

        #leftarea {
            width: 24%;
            float: left;
            margin: 0;
            padding: 0;
            color: #666666;
            font-weight: bold;
            border-right: 1px solid #666;
            min-width: 290px;
        }

        #rightarea {
            width: 74%;
            float: left;
            margin-left: 20px;
            padding: 0;
        }

        #showarea, #reqbody {
            width: 90%;
            height: 180px;
        }

        #leftarea input {
            margin: 8px 0;
        }

        #leftarea select {
            margin: 8px 0;
        }

        textarea {
            font-size: 12px;
            font-family: "Courier New";
        }

        .hidden {
            display: none;
        }
    </style>
</HEAD>
<script type="text/javascript" src="./jquery.min.js"></script>

<script type="text/javascript">
    function B() {
        //兼容linux firefox
        $("#showarea").html('');
        $.post(
            'apiserver.php',
            $("#form1").serialize(),
            function (data) {
                $("#response_code").html(data.code);
                $("#showarea").html(data.body);
            }, 'json'
        );

        return false;
    }

    function G(id) {
        return document.getElementById(id);
    }

</script>
<body>
<form id="form1">
    <h1>MOMO API V4 接口测试</h1>
    <hr size=1/>
    <div id="area">
        <div id="leftarea">

            <div>
                access_token<br/>
                <input type="text" name="access_token" id="access_token"
                       value="<?php echo isset($response['access_token']) ? $response['access_token'] : ''; ?>"/>
            </div>

            <div id="typearea">
                返回格式<br/>
                <select name="rtype" id="rtype">
                    <option value="json" <?php echo
					isset($_SESSION['rtype']) && $_SESSION['rtype'] == 'json' ? 'selected' : ''; ?>>JSON
                    </option>
                    <option value="php" <?php echo
					isset($_SESSION['rtype']) && $_SESSION['rtype'] == 'php' ? 'selected' : ''; ?>>PHP
                    </option>
                </select>
            </div>
            <div id="requestarea">
                请求方式<br/>
                <select name="reqtype" id="reqtype">
                    <option value="GET" <?php echo
					isset($_SESSION['reqtype']) && $_SESSION['reqtype'] == 'GET' ? 'selected' : ''; ?>>GET
                    </option>
                    <option value="POST" <?php echo
					isset($_SESSION['reqtype']) && $_SESSION['reqtype'] == 'POST' ? 'selected' : ''; ?>>POST
                    </option>
                    <option value="PUT" <?php echo
					isset($_SESSION['reqtype']) && $_SESSION['reqtype'] == 'PUT' ? 'selected' : ''; ?>>PUT
                    </option>
                    <option value="DELETE" <?php echo
					isset($_SESSION['reqtype']) && $_SESSION['reqtype'] == 'DELETE' ? 'selected' : ''; ?>>DELETE
                    </option>
                </select>
            </div>
            <div id="methodarea">
                方法<br/>
                <input type="text" name="method" id="method"
                       value="<?php echo isset($_SESSION['method']) ? $_SESSION['method'] : ''; ?>"/>
            </div>
            <div>
                <br/>
                <input type="submit" name="submit" value="  提交请求  " onclick="javascript:return B();return false;"/>
            </div>
            <div style="font-size: 10px;text-align: left;">
                认证信息：<br/>
				<?php
				foreach ($response as $key => $val)
				{
					if ($key == 'access_token' or $key == 'refresh_token')
					{
						echo "<span style='color: red;'>$key</span>:<br/>$val<br/>";
					}
					else
					{
						echo "<span style='color: red;'>$key</span>: $val<br/>";
					}
				}
				?>
            </div>
	        <br/>
	        <a href="./chrome?access_token=<?php echo isset($response['access_token']) ? $response['access_token'] : ''; ?>">chrome版</a>
        </div>

        <div id="rightarea">
            <h3>请求内容:</h3>
            <textarea name="reqbody" id="reqbody"><?php echo isset($_SESSION['reqbody']) ? $_SESSION['reqbody']
				: ''; ?></textarea>

            <h3>响应状态码: <span id="response_code"></span></h3>

            <h3>响应内容:</h3>
            <textarea name="response_body" id="showarea"></textarea>
        </div>
    </div>
</form>
</BODY>
</HTML>