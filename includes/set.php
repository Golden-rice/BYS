<?php

// 应用的模式
const APP_NORMAL        =   0;  //普通模式

// 系统常量
defined('ROOT')      or define('ROOT', dirname($_SERVER['SCRIPT_FILENAME']).'/');
defined('INCLUDES')  or define('INCLUDES',  ROOT.'includes/'); // 依赖地址
defined('NAMESPACE') or define('NAMESPACE',  'BYS'); // 框架命名空间

define('APP_PATH',  ROOT.'app/'); // 应用文件目录
define('CONF_PATH', INCLUDES.'config/'); // 应用配置文件目录
define('CORE_PATH', INCLUDES.'core/'); // 核心应用目录
define('LIB_PATH',  INCLUDES.'library/'); // 依赖应用目录


require CORE_PATH.'BYS.class.php'; // 框架初始化
BYS\BYS::start();
 