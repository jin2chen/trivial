<?php
$this->breadcrumbs = array(
	'角色' => array('index'),
	'管理',
);
?>
<?php $this->renderPartial('_search', array('model' => $model));?>
<h1>管理角色</h1>

<?php
$dataProvider = $model->search();
$dataProvider->pagination->pageSize = 20;
$this->widget('ext.widgets.grid.XGridView', array(
	'id' => 'admin-role-grid',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'admin.username:创建者',
		'honor',
		array(
			'name' => 'create_time',
			'htmlOptions' => array('align' => 'center')
		),
		array(
			'class' => 'XButtonColumn',
			'header' => '管理',
			'htmlOptions' => array('align' => 'center'),
			'template' => '{view} {update} {delete}',
			'buttons' => array(
				'view' => array(
					'label' => '',
					'url' => "Yii::app()->controller->createUrl('view', array('id' => \$data['id']));",
					'options' => array('class' => 'view', 'target' => '_blank', 'title' => '查看',)
				),
				'update' => array(
					'label' => '',
					'url' => "Yii::app()->controller->createUrl('update', array('id' => \$data['id']));",
					'options' => array('class' => 'update', 'target' => '_blank', 'title' => '更新')
				),
				'delete' => array(
					'label' => '',
					'url' => "Yii::app()->controller->createUrl('delete', array('id' => \$data['id']));",
					'options' => array('class' => 'delete', 'title' => '删除'),
					'attrData' => array('ajax' => true, 'confirm' => '确定删除？', 'type' => 'delete')
				),
			)
		)
	),
)); 
?>
