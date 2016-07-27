<?php
/**
 * The following variables are available in this template:
 * - $this: the CrudCode object
 */
?>
<?php
echo "<?php\n";
$label=$this->pluralize($this->class2name($this->modelClass));
echo "\$this->breadcrumbs = array(
	'$label' => array('index'),
	'Manage',
);\n";
?>
?>
<?php echo "<?php \$this->renderPartial('_search', array('model' => \$model));?>\n"; ?>
<h1>Manage <?php echo $this->pluralize($this->class2name($this->modelClass)); ?></h1>

<?php echo "<?php\n"; ?>
$dataProvider = $model->search();
$dataProvider->pagination->pageSize = 20;
$this->widget('ext.widgets.grid.XGridView', array(
	'id' => '<?php echo $this->class2id($this->modelClass); ?>-grid',
	'dataProvider' => $dataProvider,
	'columns' => array(
<?php
$count=0;
foreach($this->tableSchema->columns as $column)
{
	if(++$count==7)
		echo "\t\t/*\n";
	echo "\t\t'".$column->name."',\n";
}
if($count>=7)
	echo "\t\t*/\n";
?>
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
