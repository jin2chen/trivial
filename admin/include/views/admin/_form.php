<div class="form">

<?php 
$form=$this->beginWidget('CActiveForm', array(
	'id' => 'admin-form',
	'enableAjaxValidation' => false,
)); 
?>

	<p class="note">带 <span class="required">*</span> 为必填项</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model, 'admin_role_id'); ?>
		<?php echo $form->dropDownList($model, 'admin_role_id', $this->roles); ?>
		<?php echo $form->error($model, 'admin_role_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model, 'username'); ?>
		<?php echo $form->textField($model, 'username', array('size' => 25, 'maxlength' => 25)); ?>
		<?php echo $form->error($model, 'username'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model, 'realname'); ?>
		<?php echo $form->textField($model, 'realname',array('size' => 25,'maxlength' => 25)); ?>
		<?php echo $form->error($model, 'realname'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? '创建' : '保存'); ?>
	</div>

<?php $this->endWidget(); ?>

</div>