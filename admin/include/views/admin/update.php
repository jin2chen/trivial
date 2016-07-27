<?php
$this->breadcrumbs = array(
	'帐号' => array('index'),
	$model->id => array('view','id' => $model->id),
	'更新',
);
?>

<h1>更新帐号 <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model' => $model)); ?>