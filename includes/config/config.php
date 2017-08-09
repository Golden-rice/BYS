<?php
	/** 
   *  应用配置
   */
	return array(
		// 公共配置
		'COMMON' => array(
			// 模板变量替换
			'TPL_VAR' => array(
				'__PUBLIC__' => URL.'public/',
				'__URL__' => URL.'index.php/'
			)
		),

		// 应用admin的配置
		'APP_admin' => array(

		)
	);