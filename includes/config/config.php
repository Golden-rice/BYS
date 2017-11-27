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
				'__URL__' => URL.'index.php/',
				'__VERSION__' => date('YmdHis',time()), //年月日小时
				'__CONTROLLER__' => URL.'index.php/'.self::$_GLOBAL['app'].'/'.self::$_GLOBAL['con'].'/', // 当前控制器
				'__ACTION__' => URL.'index.php/'.self::$_GLOBAL['app'].'/'.self::$_GLOBAL['con'].'/'.self::$_GLOBAL['act'].'/' // 当前控制器
			),

			// 数据库设置
			'DB_CONFIG' => array(
					'DB_TYPE'   => 'mysql',                   // 数据库类型
					'DB_HOST'   => 'zhonghangyixiang.mysql.rds.aliyuncs.com', // 服务器地址
					'DB_NAME'   => 'dongmin',                 // 数据库名
					'DB_USER'   => 'dongmin',                 // 用户名
					'DB_PWD'    => 'dongmin@123456',          // 密码
					'DB_PORT'   => 3306,                      // 端口
					'DB_CHARSET'=> 'utf8',                    // 字符集
			)
		),

		// 应用admin的配置
		'APP_admin' => array(

		)
	);