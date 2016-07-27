<div class="form">

<?php 
$form=$this->beginWidget('CActiveForm', array(
	'id' => 'pwd-form',
	'enableAjaxValidation' => false,
)); 
?>

	<p class="note">带 <span class="required">*</span> 为必填项</p>

	<?php echo $form->errorSummary($model); ?>
	
	<?php if ($self): ?>
	<div class="row">
		<?php echo $form->labelEx($model, 'old'); ?>
		<?php echo $form->passwordField($model, 'old', array('autocomplete' => 'off', 'value' => '')); ?>
		<?php echo $form->error($model, 'old'); ?>
	</div>
	<?php endif;?>

	<div class="row">
		<?php echo $form->labelEx($model, 'new'); ?>
		<?php echo $form->passwordField($model, 'new', array('autocomplete' => 'off', 'value' => '')); ?>
		<?php echo $form->error($model, 'new'); ?>
	</div>
	
	<div class="row">
		<?php echo $form->labelEx($model, 'cnf'); ?>
		<?php echo $form->passwordField($model, 'cnf', array('autocomplete' => 'off', 'value' => '')); ?>
		<?php echo $form->error($model, 'cnf'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($self ? '更新' : '设置'); ?>
	</div>

<?php $this->endWidget(); ?>

</div>