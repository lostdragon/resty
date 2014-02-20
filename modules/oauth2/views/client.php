<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<a href="client_list">返回列表</a>

<h1><?php echo isset($_GET['client_id']) ? '修改' : '注册';?>MOMO应用</h1>

<form method="post" action="">
    <input type="hidden" name="client_id" value="<?php echo isset($_GET['client_id']) ? $_GET['client_id'] : '';?>">

    <p>
        <label for="client_name">应用名称</label><br/>
        <input id="client_name" class="text" name="client_name" type="text"
               value="<?php echo isset($client['client_name']) ? $client['client_name'] : '';?>"/>
    </p>

    <p>
        <label for="client_logo">应用LOGO</label><br/>
        <input id="client_logo" class="text" name="client_logo" type="text"
               value="<?php echo isset($client['client_logo']) ? $client['client_logo'] : '';?>"/>
    </p>

    <p>
        <label for="client_desc">应用介绍</label><br/>
        <textarea id="client_desc" class="text" name="client_desc" cols="60"
                  rows="4"><?php echo isset($client['client_desc']) ? $client['client_desc'] : '';?></textarea>
    </p>

    <p>
        <label for="client_type">应用类型</label><br/>
        <select id="client_type" name="client_type">
            <option value="0" <?php echo
				(isset($client['client_type']) AND $client['client_type'] == 0) ? 'selected="true"' : '';?>>网站
            </option>
            <option value="1" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 1) ? 'selected="true"' : '';?>>android客户端
            </option>
            <option value="2" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 2) ? 'selected="true"' : '';?>>iphone客户端
            </option>
            <option value="3" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 3) ? 'selected="true"' : '';?>>windows mobile客户端
            </option>
            <option value="4" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 4) ? 'selected="true"' : '';?>>s60v3客户端
            </option>
            <option value="5" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 5) ? 'selected="true"' : '';?>>s60v5客户端
            </option>
            <option value="6" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 6) ? 'selected="true"' : '';?>>java客户端
            </option>
            <option value="7" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 7) ? 'selected="true"' : '';?>>webos客户端
            </option>
            <option value="8" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 8) ? 'selected="true"' : '';?>>blackberry客户端
            </option>
            <option value="9" <?php echo
				(isset($client['client_type'])  AND $client['client_type'] == 9) ? 'selected="true"' : '';?>>ipad客户端
            </option>
            <option value="10" <?php echo
				(isset($client['client_type']) AND $client['client_type'] == 10) ? 'selected="true"' : '';?>>web手机端
            </option>
            <option value="11" <?php echo
				(isset($client['client_type']) AND $client['client_type'] == 11) ? 'selected="true"' : '';?>>web手机触屏版
            </option>
        </select>
    </p>

    <p>
        <label for="client_uri">网站地址</label><br/>
        <input id="client_uri" class="text" name="client_uri" type="text"
               value="<?php echo isset($client['client_uri']) ? $client['client_uri'] : '';?>"/>
    </p>

    <p>
        <label for="redirect_uri">回调地址</label><br/>
        <input id="redirect_uri" class="text" name="redirect_uri" type="text"
               value="<?php echo isset($client['redirect_uri']) ? $client['redirect_uri'] : '';?>"/>
    </p>

    <br/>
    <input type="submit" value="提交"/>
</form>
</body>
</html>