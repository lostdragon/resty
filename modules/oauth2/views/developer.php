<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<a href="../_tools">返回菜单</a>

<h1>修改MOMO开发者</h1>
<form method="post" action="">

    <fieldset>
        <legend>开发者信息</legend>
        <p>
            <label for="dev_type">开发者类型</label><br/>
	        <select name="dev_type" id="dev_type">
		        <option value="person" <?php echo
		        (isset($client['dev_type']) AND $client['dev_type'] == 'person') ? 'selected="true"' : '';?>>person</option>
                <option value="company" <?php echo
                (isset($client['dev_type']) AND $client['dev_type'] == 'company') ? 'selected="true"' : '';?>>company</option>
	        </select>
        </p>
        <p>
            <label for="dev_name">姓名</label><br/>
            <input class="text" id="dev_name" name="dev_name" type="text" value="<?php echo isset($developer['dev_name']) ? $developer['dev_name'] : '';?>"/>
        </p>
        <p>
            <label for="dev_desc">简介</label><br/>
            <input class="text" id="dev_desc" name="dev_desc" type="text" value="<?php echo isset($developer['dev_desc']) ? $developer['dev_desc'] : '';?>"/>
        </p>

        <p>
            <label for="dev_email">邮件</label><br/>
            <input class="text" id="dev_email" name="dev_email" type="text" value="<?php echo isset($developer['dev_email']) ? $developer['dev_email'] : '';?>"/>
        </p>
        <p>
            <label for="dev_tel">电话</label><br/>
            <input class="text" id="dev_tel" name="dev_tel" type="text" value="<?php echo isset($developer['dev_tel']) ? $developer['dev_tel'] : '';?>"/>
        </p>
    </fieldset>

    <br/>
    <input type="submit" value="提交"/>
</form>

</body>
</html>