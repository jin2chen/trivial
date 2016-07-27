<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<base target="main">
<title>TOP-<?php echo Yii::app()->name;?></title>
<link rel="stylesheet" href="<?php echo $this->asset('/css/frame.css');?>" />
<script src="<?php echo $this->asset('/js/jquery/jquery.js');?>"></script>
</head>
<body class="top-body">
<div class="head">
	<div class="head-nav">
		<div class="head-logo"></div>
		<div class="head-link">
			<ul class="head-link-ul">
				<li class="head-welcome">您好：<?php echo $this->user->realname;?> </li>
				<li><a target="menu" href="<?php echo $this->createUrl('menu');?>">主菜单</a></li>
				<li><a href="<?php echo $this->createUrl('reset');?>">修改密码</a></li>
				<li><a target="_top" href="<?php echo $this->createUrl('logout');?>">注销</a></li>
			</ul>
		</div>
	</div>
	<div class="head-fn">
		<div class="menuact">
			<a id="menu" class="toggle-menu" href="javascript:;">隐藏菜单</a>
			<a id="remenu" class="all-menu" href="javascript:;">重载菜单</a>	
			<a id="refresh" class="refresh" href="javascript:;">刷新</a>
		</div>
		<div id="ajax" class="loading">数据处理中。。。</div>
	</div>
</div>
<script>
(function($) {
	$("#menu").click(function(e) {
		var $this = $(this);
		var fset = window.top.document.getElementById("menu-main");
		if (fset.cols == "0,*") {
			fset.cols = "212,*";
			$this.text("隐藏菜单");
		} else {
			fset.cols = "0,*";
			$this.text("显示菜单");
		}
	});
	$("#refresh").click(function(e) {
		window.top.frames["main"].location.reload();
	});
	$("#remenu").click(function(e) {
		window.top.frames["menu"].location.reload();
	});
})(jQuery);
</script>
</body>
</html>