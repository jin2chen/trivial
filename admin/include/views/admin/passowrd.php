<?php
$_act = $this->getAction()->getId();
$this->pageTitle = '更新密码';
?>
<?php echo EHtml::beginForm();?>
<fieldset>
  <legend><?php echo $this->pageTitle;?></legend>
  <table class="post">
    <tbody>
      <tr>
        <td width="100"><?php echo EHtml::activeLabelEx($model, 'username'); ?></td>
        <td>
          <?php echo $model->username;?>
        </td>
      </tr>
      <tr>
        <td width="100"><?php echo EHtml::activeLabelEx($model, 'password'); ?></td>
        <td>
          <?php echo EHtml::activePasswordField($model, 'password', array('class' => 'text', 'autocomplete' => 'off'));?>
        </td>
      </tr>
      <tr>
        <td><?php echo EHtml::activeLabelEx($model,'repassword'); ?></td>
        <td><?php echo EHtml::activePasswordField($model, 'repassword', array('class' => 'text', 'autocomplete' => 'off'))?></td>
      </tr>
      <tr>
        <td><input type="submit" value="提交" /></td>
        <td></td>
      </tr>
    </tbody>
  </table>
</fieldset>
<?php echo EHtml::endForm();?>

