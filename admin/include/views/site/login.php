<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script>
if (window.top != window) {
	window.top.location = window.location;
}
</script>
<title>登录-<?php echo Yii::app()->name;?></title>
<link rel="stylesheet" href="<?php echo $this->asset('/css/frame.css');?>" />
</head>
<body class="login-body">
	<div class="login-box">
		<div class="login-top"></div>
		<?php if ($this->hasError()):?>
		<div class="msg-failure">
			<ul>
				<?php foreach ($this->getError() as $error):?>
				<li><?php echo $error;?></li>
				<?php endforeach;?>
			</ul>
		</div>
		<?php endif;?>
		<div class="login-main">
			<form name="form1" method="post">
				<dl>
					<dt>用户名：</dt>
					<dd><input autocomplete="off" class="w128" type="text" name="username"/></dd>
					<dt>密&nbsp;&nbsp;码：</dt>
					<dd><input autocomplete="off" class="w128" type="password" name="password"/></dd>
					<!--
					<dt>验证码：</dt>
					<dd><input id="vdcode" type="text" name="validate" style="text-transform:uppercase;"/><img id="vdimgck" align="absmiddle" onClick="this.src=this.src+'?'" style="cursor: pointer;" alt="看不清？点击更换" src="#"/>
					<a href="javascript:;" >看不清？ </a></dd>-->
					<dt>&nbsp;</dt>
					<dd><input type="submit" class="btn-login" value="登录"></dd>
				</dl>
			</form>
		</div>
		<div class="login-power">&copy; 2010-2011</div>
	</div>
</body>
</html>
