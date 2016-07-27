<?php
/**
 * Web App local config file
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: test.php 193 2011-12-07 12:37:51Z mole1230 $
 */
return CMap::mergeArray(require (dirname(__FILE__) . '/main.php'), array(
	'import' => array(
		'application.extensions.yiidebugtb.*',
	),
	
	'modules' => array(
		'gii' => array(
			'class' => 'system.gii.GiiModule',
			'password' => '123456',
			'generatorPaths' => array(
				'application.extensions.gii'
			),
		),
	),
	
	'components' => array(		
		'log' => array(
			'class' => 'CLogRouter', 
			'routes' => array(
				array( // configuration for the toolbar
					'class' => 'XWebDebugRouter', 
					'config' => 'alignLeft, opaque, runInDebug, fixedPos, collapsed, yamlStyle', 
					'levels' => 'error, warning, trace, profile, info'
				), 
//				array(
//					'class' => 'ext.firephp.SFirePHPLogRoute', 
//					'levels' => 'error, warning, info, trace'
//				),
			)
		)
	)
));

