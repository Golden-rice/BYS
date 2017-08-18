<?php

namespace BYS;
/**
 * Dispatcher类
 * 完成URL解析、路由和调度
 * URL 匹配模式： App/Module/Controller/Action
 */
class Dispatcher {

  /**
   * 路由
   * @access public
   * @return void
   */
	static public function dispatch(){

		$host = $_SERVER['HTTP_HOST'];
		$url = array(
			$host => array(
				"HOST" => $_SERVER['HTTP_HOST'],
				"PATH" => $_SERVER['PHP_SELF']
			)
		);

		// 是否为URL伪静态访问
		if(isset($_SERVER['PATH_INFO'])) {
			$_SERVER['PATH_INFO'] = trim($_SERVER['PATH_INFO'], '/');

			if ($_SERVER['PATH_INFO'] != ""){
				$paths = explode("/", $_SERVER['PATH_INFO']);

				// 加载应用
				$app = $paths[0];
				// 加载控制器
				$controller = $paths[1];
				// 加载方法
				$action = $paths[2];
			}
			// 默认设置
			elseif(BYS::$default['default']){
				$app = BYS::$default['default']['app'];
				$controller = BYS::$default['default']['controller'];
				$action = BYS::$default['default']['action'];
			}

			// 加载参数
			if($_SERVER['QUERY_STRING']) $params = $_SERVER['QUERY_STRING'];

			// 定位文件
			$localfiles = substr($_SERVER['SCRIPT_FILENAME'], 0, -9);

			// 生成路径
			$path = "app/$app/Controller/".$controller."Controller.class.php";

			if( is_file($localfiles.$path) ){
				// 配置路由指向
				BYS::$_GLOBAL['app'] = $app;
				BYS::$_GLOBAL['con'] = $controller;
				BYS::$_GLOBAL['act'] = $action;

				BYS::$_GLOBAL['con_path'] = $localfiles.$path;

			}else{ 
				if( !is_dir("/app/$app")){
					Report::error("无该应用");
				}elseif( !is_file($path) ){
					Report::error("无该控制器");
				}
				exit;
			}
		}else{

		}

		// 拆分APP、模块、控制器、动作

		// 映射到应用

		// 映射到控制器
		
	}
	
}