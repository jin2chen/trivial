<?php
$this->breadcrumbs = array(
	'帐号' => array('index'),
	$model->id,
);
?>

<h1>查看 Admin #<?php echo $model->id; ?></h1>

<?php $this->widget('ext.widgets.XDetailView', array(
	'data' => $model,
	'attributes' => array(
		'id',
		'admin_role_id',
		'parent_id',
		'parent_path',
		'username',
		'password',
		'realname',
		'status',
		'create_time',
		'update_time',
	),
)); 
?>
