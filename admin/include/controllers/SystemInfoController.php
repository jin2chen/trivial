<?php
/**
 * Description...
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: SystemInfoController.php 172 2011-09-28 02:30:26Z mole1230 $
 */
class SystemInfoController extends XController
{	
	public function actionPhp()
	{
		phpinfo();
	}
}
