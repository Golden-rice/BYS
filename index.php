<?php
// +----------------------------------------------------------------------
// | BYS [ BY YOUR SELF ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://donggg.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: dimmer <279452970t@qq.com>
// +----------------------------------------------------------------------


// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 绑定应用
define('BIND_APP', 'admin');

// 引入入口文件
require './includes/set.php';

