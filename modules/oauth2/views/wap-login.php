<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<meta content="telephone=no" name="format-detection"/>
<title>MOMO 登陆</title>
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
<style type="text/css">
html, body, h1, h2, h3, form, table, tr, td {
    margin: 0;
    padding: 0
}

body {
    background: #F5F8FD;
    line-height: 130%;
    color: #555;
    font-family: Arial, sans-serif;
    font-size: 16px
}

ul, ol, li {
    margin: 0;
    padding: 0;
    list-style-type: none
}

p {
    margin: 0;
    padding: 0;
    line-height: 1.2em;
    padding: 2px
}

div, span {
    margin: 0;
    padding: 0;
    line-height: 1.5em
}

img {
    border: 0;
    margin: 0;
    padding: 0
}

a {
    color: #349DE3;
    text-decoration: none;
    margin: 0 2px
}

a:hover, .curr {
    color: #fff;
    background: #349DE3
}

.btn {
    background: #349DE3;
    color: #fff;
    border-color: #69c;
    border-width: 1px;
    padding: 2px
}

.ipt-1 {
    width: 98%;
    border: 1px solid #ccc;
    font-size: 16px;
    padding: 2px
}

.ipt-2 {
    border: 1px solid #ccc;
    font-size: 16px;
    padding: 2px
}

.error {
    color: red;
    background: #FFFFD1;
    width: 98%;
    margin: 2px;
    padding: 2px
}

.success {
    color: green;
    background: #E6F3FB;
    width: 98%;
    margin: 2px;
    padding: 2px
}

.tip-1 {
    color: #EE3B3B;
    margin: 1px
}

.hide {
    display: none
}

.clear {
    clear: both
}

#topbar {
    background-color: #349DE3;
    border-bottom: 1px solid #333;
    height: 1.8em;
    text-align: left;
    vertical-align: middle
}

#topbar a {
    color: #FFF;
    font-size: 18px;
    font-weight: bold
}

.nav {
    background: #DFECF8;
    line-height: 1.5;
    padding: 2px
}

.nav-sub {
    background: #DFECF8;
    padding: 2px
}

.footer {
    padding-top: 2px;
    margin: 10px 2px;
    text-align: center
}

.dynamic-item {
    margin-bottom: 4px
}

.dynamic-opt {
    margin-bottom: 4px
}

.contact-info li {
    padding: 3px 0
}

hr {
    margin: 0;
    padding: 0;
    border: none;
    border-top: 1px dotted #CCC;
    clear: both
}

.s-line {
    margin: 0;
    padding: 0;
    border: none;
    border-top: 1px solid #9DB8C8
}

.content {
    padding: 0 2px
}

.back {
    text-align: right;
    border-bottom: dashed 1px #ccc
}

.back-1 {
    text-align: right;
    width: 100%
}

.source {
    background: #C1D3EC
}

.contact-list p {
    margin-top: 10px
}

.contact-list hr {
    margin-top: 8px
}

.chat-user p {
    padding: 5px
}

.reply-item {
    background: #C1D3EC;
    margin-bottom: 1px
}

.time {
    color: #AAA
}

.chat-me {
    background: #C1D3EC;
    margin-bottom: 1px
}

#login {
    background: #E5F4FF;
    margin-bottom: -10px
}</style>
</head>
<body>
<div id="topbar">momo.im</div>
<div class="content" id="login">
    <form action="" method="POST">
        <p> 手机号:</p>

        <p><input name="account" class="ipt-1" type="text" value=""/></p>

        <p> 密码:</p>

        <p><input name="password" class="ipt-1" type="password"/></p>
		<?php
		if ($error):
			echo '<p style="margin-top:10px;" class="error">' . $error . '</p>';
		endif;
		?>
        <p style="text-align:left">
            <input type="submit" class="btn" value=" 登 录 "/></p>
    </form>
</div>
<div class="footer">
    <hr class="s-line"/>
    <p style="color:#aaa;">Copyright © MOMO.im</p></div>
</body>
</html>