<fieldset class="search">
<legend>搜索</legend>

<?php
$form = $this->beginWidget('CActiveForm', array(
	'action' => Yii::app()->createUrl($this->route),
	'method' => 'get',
)); 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'honor'); ?>
		<?php echo $form->textField($model, 'honor', array('size' => 15, 'maxlength' => 15)); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('搜索'); ?>
	</div>

<?php $this->endWidget(); ?>

</fieldset>