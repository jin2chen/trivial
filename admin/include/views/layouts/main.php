<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo CHtml::encode($this->pageTitle);?>-<?php echo Yii::app()->name;?></title>
<link rel="stylesheet" href="<?php echo $this->asset('/css/main.css');?>" />
<?php echo $this->renderCssFiles();?>
<script src="<?php echo $this->asset('/js/jquery/jquery.js');?>"></script>
<script src="<?php echo $this->asset('/js/libadmin.js');?>"></script>
<?php echo $this->renderJsFiles();?>
</head>
<body>
<div id="msgbox" class="msgbox-wrap">
	<span class="msgbox-layer">
		<span id="msgbox-content"></span>
		<span class="msgbox-deco-left"></span>
		<span class="msgbox-deco-right"></span>
	</span>
</div>
<div class="wrap">
<?php if (isset($this->breadcrumbs)):?>
<?php 
$this->widget('zii.widgets.CBreadcrumbs', array(
	'links' => $this->breadcrumbs,
	'homeLink' => CHtml::link('首页', $this->createUrl('site/home'))
)); 
?>
<?php endif?>
<?php if ($this->hasInfo()):?>
<div class="msg-success"><?php echo $this->getInfo();?></div>
<?php endif;?>
<?php if ($this->hasError()):?>
<div class="msg-failure"><?php echo $this->getError();?></div>
<?php endif;?>
<div class="content">
<?php echo $content, "\n"; ?>
</div>
<div class="footer"></div>
</div>
</body>
</html>