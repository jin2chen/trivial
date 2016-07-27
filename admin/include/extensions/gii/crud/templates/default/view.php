<?php
/**
 * The following variables are available in this template:
 * - $this: the CrudCode object
 */
?>
<?php
echo "<?php\n";
$nameColumn=$this->guessNameColumn($this->tableSchema->columns);
$label=$this->pluralize($this->class2name($this->modelClass));
echo "\$this->breadcrumbs = array(
	'$label' => array('index'),
	\$model->{$nameColumn},
);\n";
?>
?>

<h1>查看 <?php echo $this->modelClass." #<?php echo \$model->{$this->tableSchema->primaryKey}; ?>"; ?></h1>

<?php echo "<?php"; ?> $this->widget('ext.widgets.XDetailView', array(
	'data' => $model,
	'attributes' => array(
<?php
foreach($this->tableSchema->columns as $column)
	echo "\t\t'".$column->name."',\n";
?>
	),
)); 
?>
