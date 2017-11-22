<?php

// 应用的模式
const APP_NORMAL        =   0;  //普通模式

// 系统常量
defined('ROOT')      or define('ROOT', dirname($_SERVER['SCRIPT_FILENAME']).'/');
defined('URL')       or define('URL', 'http://'.$_SERVER['HTTP_HOST'].rtrim($_SERVER['SCRIPT_NAME'], 'index.php')); 
defined('INCLUDES')  or define('INCLUDES',  ROOT.'Includes/'); // 依赖地址
defined('NAMESPACE') or define('NAMESPACE',  'BYS'); // 框架命名空间
defined('APP_DEBUG') or define('APP_DEBUG',  false); // 开发模式

define('APP_PATH',  ROOT.'App/');         // 应用文件目录
define('PUB_PATH',  ROOT.'Public/');      // 静态文件目录
define('CONF_PATH', INCLUDES.'config/');  // 应用配置文件目录
define('LIB_PATH',  INCLUDES.'library/'); // 依赖应用目录
define('CORE_PATH', LIB_PATH.'core/');    // 核心应用目录
define('VEND_PATH', LIB_PATH.'vender/');  // 第三方应用目录
define('COMMON_PATH', INCLUDES.'common/');// 公共系统函数目录
define('VIEW_TYPE', 'public');            // view 视图模式：public/private
define('VIEW_EXD',  '.html');             // view 模板


// 定义当前请求的系统常量
define('TIME',      $_SERVER['REQUEST_TIME']);
define('REQUEST_METHOD',$_SERVER['REQUEST_METHOD']);

require CORE_PATH.'BYS.class.php';        // 框架初始化
BYS\BYS::start();