<?php
/* @var $this XController */
$this->pageTitle = '设置密码';
$this->breadcrumbs = array(
	'帐号' => array('index'),
	'设置密码'
);
?>
<h1>设置密码</h1>

<?php echo $this->renderPartial('//share/pwd', array('model' => $model, 'self' => false)); ?>

