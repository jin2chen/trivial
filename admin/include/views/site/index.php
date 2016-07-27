<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<title><?php echo Yii::app()->name;?></title>
</head>
<frameset border="0" frameborder="0" rows="66,*">
<frame name="top-frame" noresize="noresize" scrolling="no" src="<?php echo $this->createUrl('top');?>" />
<frameset id="menu-main" border="0" cols="212,*" frameborder="0">
	<frame name="menu" noresize="noresize" scrolling="auto" src="<?php echo $this->createUrl('menu');?>" />
	<frame name="main" src="<?php echo $this->createUrl('home');?>" />
</frameset>
<noframes>
	<body>
	</body>
</noframes>
</frameset>
</html>
