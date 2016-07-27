<?php
$this->breadcrumbs = array(
	'角色' => array('index'),
	$model->id,
);
?>

<h1>查看 AdminRole #<?php echo $model->id; ?></h1>

<?php $this->widget('ext.widgets.XDetailView', array(
	'data' => $model,
	'attributes' => array(
		'id',
		'admin_id',
		'admin_path',
		'honor',
		'create_time',
		'update_time',
	),
)); 
?>

<?php
$this->renderPartial('//share/acl', array('menu' => $this->assembleMenu($model->id, json_decode($model->acls, true))));
?>
