<?php
$this->breadcrumbs = array(
	'帐号' => array('index'),
	'创建',
);
?>

<h1>创建帐号</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>