<?php
$this->breadcrumbs = array(
	'帐号' => array('index'),
	'管理',
);
?>
<?php $this->renderPartial('_search', array('model' => $model));?>
<h1>管理帐号</h1>

<?php
$dataProvider = $model->search();
$dataProvider->pagination->pageSize = 20;
$this->widget('ext.widgets.grid.XGridView', array(
	'id' => 'admin-grid',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		array(
			'class' => 'XLinkColumn',
			'labelExpression' => "CHtml::value(\$data, 'adminRole.honor');",
			'urlExpression' => "Yii::app()->controller->createUrl('adminRole/view', array('id' => \$data['id']));",
			'header' => '角色',
			'linkHtmlOptions' => array('target' => '_blank')
		),
		array(
			'name' => 'parent.username',
			'header' => '创建者',
			'defaultValue' => '系统'
		),
		'username',
		'realname',
		array(
			'name' => 'create_time',
			'htmlOptions' => array('align' => 'center')
		),
		array(
			'name' => 'last_time',
			'htmlOptions' => array('align' => 'center')
		),
		array(
			'name' => 'last_ip',
			'htmlOptions' => array('align' => 'center')
		),
		array(
			'class' => 'XButtonColumn',
			'header' => '管理',
			'htmlOptions' => array('align' => 'center'),
			//'template' => '{view} {update} {delete} {reset} <br /> {pick}',
			'template' => '{view} {update} {delete} {reset}',
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
				'reset' => array(
					'label' => '',
					'url' => "Yii::app()->controller->createUrl('reset', array('id' => \$data['id']));",
					'options' => array('class' => 'reset', 'target' => '_blank', 'title' => '设置密码')
				),
//				'pick' => array(
//					'evalLabel' => "XDict::\$pickLabels[0];",
//					'url' => "Yii::app()->controller->createUrl('pick', array('id' => \$data['id']));",
//					'attrData' => array('ajax' => true, 'type' => 'toggle', 'labels' => XDict::$pickLabels)
//				)
			)
		),
	),
)); 
?>
