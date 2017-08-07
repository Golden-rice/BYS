<?php
	/** 
   * 框架基础
   */
	return array(

		// 应用配置
		'config' => array( 
			'name'   => 'default_config',
			'path'   => CONF_PATH,
			'file'   => 'config.php'
		),

		// 框架核心
		'core'   =>  array(
			'path'   => CORE_PATH,
		),

		// 框架应用地图
		'appMap' => array(
			'admin'   => array(
				"name"    => "admin",
				"type"    => APP_NORMAL
			)
		),

		// 增加扩展
		'vender'    => array(
			// 临时使用：smarty 
			'smarty'   => array(
				// 应用配置
				'path'    => VEND_PATH.'smarty/',
				'config' => array( 
					'name'   => 'smarty_config',
					'file'   => 'smarty.config.php',
					'path'   => CONF_PATH
				),
			)
		),

		// 应用默认值
		'default' => array(
			'controller' => 'user',
			'action'     => 'index',
			'app'        => 'admin'
		),

		// 前端
		'fontEnd' => array(
			'config'  => array(
				'AMD'  => 'require',
				)
		)
	);
