<?php
/**
 * This is the bootstrap file for application.
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: index.php 188 2011-12-05 14:56:52Z mole1230 $
 */

define('ROOT_PATH', dirname(__FILE__));
define('PROJECT', 'admin.mole.com');
define('TIMESTAMP', time());
define('TIMEDATE', date('Y-m-d H:i:s', TIMESTAMP));

// set environment
if (@$_SERVER['ENV_DEV']) {
	error_reporting(E_ALL | E_STRICT);
	defined('YII_DEBUG') or define('YII_DEBUG', true);
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);
	$config = ROOT_PATH . '/include/config/test.php';
} else {
	$config = ROOT_PATH . '/include/config/main.php';
}

// require YiiBase.php
if (isset($_SERVER['YII_BASE_FILE'])) {
	require_once $_SERVER['YII_BASE_FILE'];
} else {
	require_once ROOT_PATH . '/lib/yii-1.1.8/YiiBase.php';
}

// bootstrap
require_once ROOT_PATH . '/Yii.php';
Yii::createWebApplication($config)->run();
