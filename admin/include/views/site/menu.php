<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<base target="main">
<title>MENU-<?php echo Yii::app()->name;?></title>
<link rel="stylesheet" href="<?php echo $this->asset('/css/frame.css');?>" />
<script src="<?php echo $this->asset('/js/jquery/jquery.js');?>"></script>
</head>
<body class="menu-body">
<table width="187" border="0" cellspacing="0" cellpadding="0" align="left">
	<tbody>
		<tr>
			<td id="module" class="module" width="27" valign="top">
				<ul>
					<?php 
					$modules = array_keys($menu);
					foreach ($modules as $name):
					?>
					<li><?php echo $name;?></li>
					<?php endforeach;?>
				</ul>
			</td>
			<td id="list" class="list" width="160" align="left" valign="top">
				<?php foreach ($menu as $module):?>
				<div>
					<?php foreach ($module as $ctrlKey => $ctrl):?>
					<dl>
						<dt><strong><?php echo $ctrl['title'];?></strong></dt>
						<dd>
						<?php if (!empty($ctrl['actions'])):?>
						<ul>
							<?php 
							foreach ($ctrl['actions'] as $actKey => $act):
								if (!$act['show']) continue;
							?>
							<li><a href="<?php echo $this->createUrl($ctrlKey . '/' . $actKey);?>"><?php echo $act['title'];?></a></li>
							<?php endforeach;?>
						</ul>
						<?php endif;?>
						</dd>
					</dl>
					<?php endforeach;?>
				</div>
				<?php endforeach;?>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td class="menu-footer">&nbsp;</td>
		</tr>
	</tbody>
</table>
<script>
(function($) {
	var CMODULE = $();
	var CLIST = $();

	$("#module li").bind({
		mouseover: function(e) {$(this).addClass("s");},
		mouseout: function(e) {$(this).removeClass("s");},
		click: function(e) {
			if (this == CMODULE.get(0)) {
				return;
			}

			var $this = $(this), 
				i = $("#module li").index(this),
				$div = $("#list div").eq(i);
			CMODULE.removeClass("c");
			$this.addClass("c");
			CMODULE = $this;
			CLIST.hide();
			$div.show();
			CLIST = $div;
			return false;
		}
	});

	$("#list dt").click(function(e) {
		var $this = $(this);
		$this.parent().toggleClass("c");
		$this.next().toggle();
	});

	// exe
	$("#module li").eq(0).trigger('click');
})(jQuery);
</script>
</body>
</html>