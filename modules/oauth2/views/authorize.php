<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Authorize</title>
	<script>
		if (top != self) {
			window.document.write("<div style='background:black; opacity:0.5; filter: alpha (opacity = 50); position: absolute; top:0px; left: 0px;"
				+ "width: 9999px; height: 9999px; zindex: 1000001' onClick='top.location.href=window.location.href'></div>");
		}
	</script>
</head>
<body>
<form method="post" action="">
	<?php foreach ($auth_params as $key => $value) : ?>
	<input type="hidden"
	       name="<?php echo htmlspecialchars($key, ENT_QUOTES); ?>"
	       value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>" />
	<?php endforeach; ?>
	您要授权应用 <?php echo $client_name; ?> 访问您的 <?php echo $scope_name ?>?
	<p><input type="submit" name="accept" value="授权" /> <input
		type="submit" name="accept" value="取消" /></p>
</form>
</body>
</html>