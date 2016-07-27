<?php
/**
 * Menu list.
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: menu.php 172 2011-09-28 02:30:26Z mole1230 $
 * 
 * <code>
 * array(
 * 	'系统' => array(// 模块名
 * 		'system' => array(
 * 			'title' => '系统管理'	// 控制器名
 * 			'show' => true,			// 是否出现于菜单栏 bool
 * 			'actions' => array(
 * 				'cache' => array(	// 动作名
 * 					'title' => '管理缓存',
 * 					'show' => true, // 是否出现于菜单栏 bool
 * 				)
 * 			)
 * 		)
 * 	)
 * </code>
 */
return array(
	'系统' => array(
		'adminRole' => array(
			'title' => '角色管理',
			'show' => true,
			'actions' => array(
				'index' => array(
					'title' => '管理角色',
					'show' => true
				),
				'create' => array(
					'title' => '创建角色',
					'show' => true
				),
				'update' => array(
					'title' => '更新角色',
					'show' => false
				),
				'delete' => array(
					'title' => '删除角色',
					'show' => false
				),
			),
		),
		'admin' => array(
			'title' => '帐号管理',
			'show' => true,
			'actions' => array(
				'index' => array(
					'title' => '管理帐号',
					'show' => true
				),
				'create' => array(
					'title' => '创建帐号',
					'show' => true
				),
				'update' => array(
					'title' => '更新帐号',
					'show' => false
				),
				'delete' => array(
					'title' => '删除帐号',
					'show' => false
				),
				'password' => array(
					'title' => '更新密码',
					'show' => false
				),
			),
		),
		'systemInfo' => array(
			'title' => '系统信息',
			'show' => true,
			'actions' => array(
				'php' => array(
					'title' => 'PHP信息',
					'show' => true
				),
			),
		),
	),
	
	'项目' => array(
		'svn' => array(
			'title' => 'SVN 管理',
			'show' => true,
			'actions' => array(
				'index' => array(
					'title' => '项目列表',
					'show' => true
				),
			),
		),
	),
	
	'测试' => array(
		'sphinx' => array(
			'title' => 'Sphinx',
			'show' => true,
			'actions' => array(
				'index' => array(
					'title' => 'index',
					'show' => true
				),
			),
		),
	),
);