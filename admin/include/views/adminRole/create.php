<?php
$this->breadcrumbs = array(
	'角色' => array('index'),
	'创建',
);
?>

<h1>创建角色</h1>

<?php echo $this->renderPartial('_form', array('model' => $model, 'menu' => $menu, 'acls' => $acls)); ?>