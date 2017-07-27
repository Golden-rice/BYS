<?php

namespace BYS;

class BYS {

	public static $_map = array(); 

	/** 
	 * 应用初始化
   * @access public 
   * @return void
	 */	
	static public function start(){
		// 启用自动加载类库
		spl_autoload_register('BYS\BYS::autoload');   

		// 设置中国时区
		date_default_timezone_set('PRC');
		
		// 加载框架基础配置
		$default = include INCLUDES.'default.php';

		// 加载配置文件
		$config = include $default['config']['path'].$default['config']['file'];

		// 读取核心类库
		self::autoReadRefer("core", $default);

		Report::p(self::$_map);

		Template::test();

		// 初始化文件存储方式？
		// 读取当前应用状态对应的配置文件？

		// 检查应用目录结构 如果不存在则自动创建 sitemap
		self::autoMakeApp($default['appMap']);
		// $app     =   defined('BIND_APP') ? BIND_APP : "admin";

		// 记录加载文件时间，切面

		// 切面记录时间
		
		// 生成缓存用于模板解析

		// 运行应用
	}

	/** 
	 * 自动加载_map中的类库和用户自定义类库
	 * @access public 
	 * @param  classObject $class 待加载类名
   * @return void
	 */
	static public function autoload($class){
		if(isset(self::$_map[$class])) {
        include self::$_map[$class];
    }
	}

	/** 
	 * 注册类库_map
	 * @access public 
	 * @param  classObject $class 待加载类名
	 * @param  string      $map   待加载类库
   * @return void
	 */
	static public function registerMap($class, $map = ""){

    if(is_array($class)){
        self::$_map = array_merge(self::$_map, $class);
    }else{
        self::$_map[$class] = $map;
    } 
	}

	/** 
	 * 自动读取框架扩展
	 * @access private 
	 * @param $referType 扩展的类型
	 * @param $config    扩展的配置
   * @return void
	 */
	static private function autoReadRefer($referType, $config){

		if( is_dir($config[$referType]['path']) && $handle = opendir($config[$referType]['path']) ){
			while( ($file = readdir($handle)) !== false ){
				if( $file!='.' && $file!='..' ){
					preg_match("/^\w+/", $file, $className);
					self::registerMap( "BYS\\$className[0]", $config[$referType]['path'].$file );
				}
			}
		}else{
			// 报错: 没有该框架扩展
			Report::error("没有该扩展");
		}

	}

	/** 
	 * 自动生成应用
	 * @access private 
	 * @param $appMap    应用地图
   * @return void
	 */
	static private function autoMakeApp($appMap){
		
		foreach ($appMap as $appName => $appVal) {
			Report::p( $appVal );
			if( is_dir(APP_PATH.$appName) ){
				// 有该应用，初始化
				App::init( $appVal );
			}else{
				// 无该应用，生成生成应用并初始化
				App::build( $appVal );
			}
		}

	}

}