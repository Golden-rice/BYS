<?php

namespace BYS;

class BYS {

	// 类映射
	public static $map = array(); 

  // 实例化的对象
  private static $_instance = array();

  // 公共变量
  public static $_GLOBAL = array();

  // 配置
  public static $default;

  // 站点地图
  public static $sitemap;

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

		// 设置语言
		header('Content-Type:text/html; charset=utf-8');
		
		// 加载框架基础配置
		$default = include INCLUDES.'default.php';
		self::$default = $default;

		// 加载用户配置
		self::autoReadClass( $default["config"] );

		// 读取核心类库
		self::autoReadClass( $default["core"], constant('NAMESPACE')."\\" );

		// 加载smarty扩展
		if( isset($default['vender']) && isset($default['vender']['smarty']) ){
			// 读取smarty核心类库
			self::autoReadClass( $default['vender']['smarty'] );
		}	

		// Report::p(self::$map);

		// 初始化文件存储方式

		// 检查应用目录结构 如果不存在则自动创建 sitemap
		self::autoMakeApp($default['appMap']);

		// 记录加载文件时间，切面

		// 切面记录时间
		
		// 生成缓存用于模板解析

		// 暴露控制器路径
		define('AB_CONTROLLER_PATH', self::$map[constant('NAMESPACE')."\\Controller"]);

		// 运行应用
		App::run();
	}

	/** 
	 * 自动加载map中的类库和用户自定义类库
	 * @access public 
	 * @param  classObject $class 待加载类名
   * @return void
	 */
	static private function autoload($class){
		if(isset(self::$map[$class])) {
        include self::$map[$class];
    }
	}

	/** 
	 * 注册类库map
	 * @access public 
	 * @param  classObject $class 待加载类名
	 * @param  string      $map   待加载类库
   * @return void
	 */
	static private function registerMap($class, $map = ""){

    if(is_array($class)){
        self::$map = array_merge(self::$map, $class);
    }else{
        self::$map[$class] = $map;
    } 
	}

	/** 
	 * 返回扩展的库的实例
	 * @access public 
	 * @param  string $class  待加载类名
	 * @param  array  $config 配置
   * @return object
	 */
	static public function callVender($class, $config){

		$instance = new $class();
    // 执行扩展配置
    foreach ($config["()"] as $key => $value) {
     $instance->$key($value);
    }
    foreach ($config["="] as $key => $value) {
     $instance->$key = $value;
    }

    return $instance;
	}

	/** 
	 * 返回类的实例
	 * @access public 
	 * @param  string $class  待加载类名
	 * @param  array  $config 配置
   * @return object
	 */
	static public function callClass($class, $config = array()){
		$class = "BYS\\$class";
		$instance = new $class($config);
		return $instance;
	}

	/** 
	 * 返回配置
	 * @access public 
	 * @param  string $config     待加载类名
	 * @param  string $configType  配置类型
   * @return object
	 */
	static public function callConfig( $config = "", $configType = ""){
		$default = self::$default;

		switch ( $configType ) {
			case 'vender':
				$config_result = self::readConfig( $default['vender'][$config] ); 
				break;
			
			default:
				$config_result = self::readConfig( $default ); 
				break;
		}
		return $config_result;
	}

	/** 
	 * 自动读取类库
	 * @access private 
	 * @param  $config    扩展的配置
	 * @param  $namespace 命名空间
   * @return void
	 */
	static private function autoReadClass($config, $namespace = ""){
		if( is_dir($config['path']) && $handle = opendir($config['path']) ){
			while( ($file = readdir($handle)) !== false ){
				if( $file!='.' && $file!='..' ){
					preg_match("/^(\w+)\.class\.php/", $file, $className);
					if( isset($className[1]) ) self::registerMap( "$namespace$className[1]", $config['path'].$file );
				}
			}
		}else{
			// 报错: 没有该类库
			Report::error("没有该类库");
		}

	}

	/** 
	 * 自动读取配置
	 * @access public 
	 * @param  $config    待读取的扩展
   * @return array
	 */
	static public function readConfig($config){
		if( is_file($config['config']['path'].$config['config']['file']) && $file = $config['config']['path'].$config['config']['file'] ){
			return include $file;
		}else{
			// 报错: 没有该配置
			Report::error("没有该配置");
		}

	}

	/** 
	 * 根据用户配置使用
	 * @access private 
	 * @param $config    读取的配置
   * @return void
	 */
	// static private function useAppConfig($config){
	// 	if(!isset($config)) {
	// 		Report::error('没有配置');
	// 		return;
	// 	}

	// 	// 公共配置
	// 	if(isset($config['COMMON'])){
	// 		// TPL_VAR 扩展
	// 		Using::commoning($config['COMMON']);
	// 	}

	// 	// 各个应用配置
	// 	foreach ($config as $name => $set) {
	// 		if( preg_match("/^APP_/", $name) ){
	// 			Using::privating($set, $appName);
	// 		}
	// 	}
	// }

	/** 
	 * 自动生成应用
	 * @access private 
	 * @param $appMap    应用地图
   * @return void
	 */
	static private function autoMakeApp($appMap){
		
		foreach ($appMap as $appName => $appVal) {
			$app = new App($appVal);

			if( is_dir(APP_PATH.$appName) ){
				// 有该应用，初始化
				$app->init( $appVal );
			}else{
				// 无该应用，生成生成应用并初始化
				$app->build( $appVal );
			}
		}

	}

	/**
   * 返回对象实例 可调用类的静态方法
   * @param string $class  对象类名
   * @param string $method 类的静态方法名
   * @return object
   */
  static public function instance($class, $method='') {
      $identify   =   $class.$method;
      if(!isset(self::$_instance[$identify])) {
          if(class_exists($class)){
              $o = new $class();
              if(!empty($method) && method_exists($o, $method))
                  self::$_instance[$identify] = call_user_func(array(&$o, $method));
              else
                  self::$_instance[$identify] = $o;
          }
          else
              self::warning('不存在该类：'.$class);
      }
      return self::$_instance[$identify];
  }

}