<?php foreach ($menu as $modKey => $module):?>
<fieldset class="acl">
	<legend><?php echo $modKey;?></legend>
	<?php foreach ($module as $ctrlKey => $ctrl):?>
	<dl>
		<dt>
			<label><span><?php echo $ctrl['title'];?></span></label>
		</dt>
		<?php if (is_array($ctrl['actions'])):?>
		<dd>
			<?php foreach ($ctrl['actions'] as $actKey => $act):?>
			<?php if (!isset($act['acl']) || $act['acl'] !== false):?>
			<label><span><?php echo $act['title'];?></span></label>
			<?php endif;?>
			<?php endforeach;?>
		</dd>
		<?php endif;?>		
	</dl>
	<?php endforeach;?>
</fieldset>
<?php endforeach;?>