<div class="form">

<?php
$form = $this->beginWidget('CActiveForm', array(
	'id' => 'admin-role-form',
	'enableAjaxValidation'=>false,
));
?>

	<p class="note">带 <span class="required">*</span> 为必填项</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'honor'); ?>
		<?php echo $form->textField($model,'honor',array('size'=>15,'maxlength'=>15)); ?>
		<?php echo $form->error($model,'honor'); ?>
	</div>

	<div class="row">
		<?php foreach ($menu as $modKey => $module):?>
		<fieldset class="acl">
			<legend><?php echo $modKey;?></legend>
			<?php foreach ($module as $ctrlKey => $ctrl):?>
			<dl>
				<dt>
					<label><input type="checkbox" name="<?php $n = 'ctrls[]'; echo CHtml::resolveName($model, $n);?>" value="<?php echo $modKey . '/' . $ctrlKey;?>" <?php if (isset($acls['ctrls'][$ctrlKey])): echo 'checked="true"'; endif;?> /><span><?php echo $ctrl['title'];?></span></label>
				</dt>
				<?php if (is_array($ctrl['actions'])):?>
				<dd>
					<?php foreach ($ctrl['actions'] as $actKey => $act):?>
					<?php if (!isset($act['acl']) || $act['acl'] !== false):?>
					<label><input type="checkbox" name="<?php $n = 'acts[]'; echo CHtml::resolveName($model, $n);?>" value="<?php echo $modKey . '/' . $ctrlKey . '/' . $actKey;?>" <?php if (!isset($acls['ctrls'][$ctrlKey]) && isset($acls['acts'][$ctrlKey][$actKey])): echo 'checked="true"'; endif;?> /><span><?php echo $act['title'];?></span></label>
					<?php endif;?>
					<?php endforeach;?>
				</dd>
				<?php endif;?>		
			</dl>
			<?php endforeach;?>
		</fieldset>
		<?php endforeach;?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? '创建' : '保存'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->
<script>
$('dt input:checkbox').click(function(e) {
	if (this.checked) {
		$(this).closest('dl')
			.find('dd input:checkbox')
			.each(function(i, el) {
				this.checked = false;
			});
	}
});
$('dd input:checkbox').click(function(e) {
	$(this).closest('dl')
		.find('dt input:checkbox')
		.each(function(i, el) {
			this.checked = false;
		});
});
</script>