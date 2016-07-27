<fieldset class="search">
<legend>搜索</legend>

<?php $form = $this->beginWidget('CActiveForm', array(
	'action' => Yii::app()->createUrl($this->route),
	'method' => 'get',
)); 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'username'); ?>
		<?php echo $form->textField($model, 'username', array('size' => 25, 'maxlength' => 25)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'realname'); ?>
		<?php echo $form->textField($model, 'realname', array('size'=>25, 'maxlength' => 25)); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('搜索'); ?>
	</div>

<?php $this->endWidget(); ?>

</fieldset>