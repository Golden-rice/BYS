<?php
// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 绑定应用
define('BIND_APP', 'admin');

// 引入入口文件
require './includes/set.php';

