<html>
<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
	<title>Profile View</title>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
</head>
<body>

<input type="button" id="ok" value="ok">

<div id="oks"></div>
<script type="text/javascript">

	var params = {}, queryString = location.hash.substring(1),
		regex = /([^&=]+)=([^&]*)/g, m;
	while (m = regex.exec(queryString)) {
		params[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
	}
	$("#ok").click(function () {
		if(queryString) {
			//请求资源
			$.ajax({
				url:"http://resty.91.com/users/1?" + queryString,
				cache:false,
				dataType:"html"
			}).success(function (fin) {
					$("#oks").html(fin);
			}).fail(function(dat) {
                    $("#oks").html(dat.responseText);
            });
		} else {
			//请求获取token
			$.ajax({
				url:"http://resty.91.com/oauth2/token",
				type:"post",
				data:"grant_type=authorization_code&client_id=<?php echo $client_id; ?>&client_secret=<?php echo $client_secret; ?>&code=<?php echo $code; ?>&redirect_uri=<?php echo $redirect_uri; ?>",
				dataType:"json"
			}).success(function (dat) {
				//请求资源
				$.ajax({
					url:"http://resty.91.com/users/1?client_id=<?php echo $client_id; ?>&access_token=" + dat.access_token,
					cache:true,
					dataType:"html"
				}).success(function (fin) {
						$("#oks").html(fin);
				});
			}).fail(function(dat) {
				$("#oks").html(dat.responseText);
			});
		}
	});
</script>
</body>
</html>