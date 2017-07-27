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
		)
	);
