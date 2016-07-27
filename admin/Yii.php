<?php
/**
 * YiiBase extention
 *
 * @author mole <mole1230@gmail.com>
 * @version $Id: Yii.php 188 2011-12-05 14:56:52Z mole1230 $
 */
class Yii extends YiiBase
{
	/**
	 * Creates a Web application instance. {@link CWebApplication::createWebApplication}
	 * @param mixed $config
	 * @return XWebApplication
	 */
	public static function createWebApplication($config = null)
	{
		return self::createApplication('XWebApplication', $config);
	}
	
	public static function debug()
	{
		define('YII_DEBUG_TB', true);
	}
}

/**
 *
 */
class XWebApplication extends CWebApplication
{
	
}