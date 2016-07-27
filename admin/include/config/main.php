<?php
/**
 * 应用程序配置文件
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: main.php 189 2011-12-05 15:09:48Z mole1230 $
 */

// 应用程序配置参数
return array(
	'basePath' => dirname(__FILE__) . '/..', 
	'name' => '后台管理系统', 
	'language' => 'zh_cn', 
	
	'preload' => array(
		'log'
	), 
	
	'import' => array(
		'application.models.*', 
		'application.components.*',
		'ext.utils.Fn'
	), 
	
	// application components
	'components' => array(
		'db' => array(
			'class' => 'XDbConnection', 
			'connectionString' => "mysql:host={$_SERVER['DB_HOST']};dbname={$_SERVER['DB_NAME']};port={$_SERVER['DB_PORT']}", 
			'emulatePrepare' => true, 
			'username' => $_SERVER['DB_USER'], 
			'password' => $_SERVER['DB_PASS'], 
			'charset' => 'UTF8', 
			'tablePrefix' => ''
		),
		'sphinx' => array(
			'class' => 'ext.sphinx.DGSphinxSearch',
			'server' => '10.1.71.16',
			'port' => 9302,
		),
	), 
	
	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params' => array(
		'adminEmail' => 'mole1230@gmail.com', 
		'version' => 'alpha'
	)
);

