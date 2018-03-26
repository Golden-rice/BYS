<?php
namespace BYS;
/** Use 匹配config.php 生成相应方法
 * 
 */
class Drive {
	// 设置
	static public $set = array();

	/**
   * @param  $config    用户配置
   */
	static public function init ($config, $filePath = ''){

		if(!isset($config)) {
			Report::error('没有配置');
			return;
		}

		// 加载公共配置
		if(isset($config['COMMON'])){
			self::commoning($config['COMMON']);
		}

		// 加载各个应用的配置
		foreach ($config as $name => $set) {
			if( preg_match("/^APP_(.*)?/", $name, $str) )
				self::privating($set, $str[1] ? $str[1] : "");
		}

		// ** 每次调用 controller 会重新声明个 drive 
		// 读取应用的 Drive 至 map
		// nameDrive.class.php
		self::autoload(APP_PATH.BYS::$_GLOBAL['app'].'/Drive/');

		// 数据库模块驱动
		// 多个数据库连接
		if( isset(self::$set['DB_CONFIG_LIST']) && Db::connect(self::$set['DB_CONFIG_LIST']['DEFAULT']) ){
			
			// 通过系统函数获得数据表对象实例

			// 扩展数据表动作
			// var_dump( Db::$link);
		}
	}

	/** 
	 * 自动加载map中的类库和用户自定义类库
	 * @access public 
	 * @param  classObject $class 待加载类名
   * @return void
	 */
	static public function autoload($path){
		if( is_dir($path) && $handle = opendir($path) ){
			while( ($file = readdir($handle)) !== false ){
				if( $file!='.' && $file!='..' ){
					preg_match("/^(\w+)\.class\.php/", $file, $className);
					// 注册至 map 中
					if( isset($className[1]) ) include_once $path.$file;
				}
			}
		}
	}

	// COMMON 配置应用
	static public function commoning($set = array()){
		self::$set = $set; 
	}

	/**
   * APP 配置应用
   * @access public
   * @param  $set      设置
   * @param  $appName  应用名
   * @return void
   */
	static public function privating($set = array(), $appName = ""){
		if(BYS::$_GLOBAL['app'] == $appName){
			self::$set = array_merge(self::$set, $set); 
		}else{
			self::commoning($set);
		}
	}

	/**
   * 驱动支持
   * @access public
   * @param  $filePath   模板文件路径
   */
	static public function support( $filePath ){
		// 替换文本
		$content = self::replaceVarContent( $filePath );
		file_put_contents( Cache::$runtime_path , $content);
	}

	/**
   * 替换文本
   * @access public
   * @param  $filePath   模板文件
   * @return string      替换模板后的结果
   */
	static public function replaceVarContent($filePath){
		if (!isset(self::$set['TPL_VAR']) || !is_file($filePath)) return;
		$replace = self::$set['TPL_VAR'];
		return str_replace( array_keys($replace), array_values($replace), file_get_contents($filePath) );
	}


	/**
   * 支持smarty模板引擎的继承功能，将相应文件复制至缓存文件夹中
   * @access public
   */
	static public function supportSmartyTpl($filePath){
		if($filePath && !is_file($filePath)) return;

		$content = file_get_contents($filePath);
		$path    = dirname($filePath);

		// 查找 extend 仅匹配一次
		if( self::hasTags( $content, 'extends') && $extendMatches = self::hasTags( $content, 'extends') ){

				$extendFile = self::parseExtend( $extendMatches );
				$extendPath = self::camparePath( $extendFile , $path );
				$runtime_path = Cache::cache($extendPath);

				if($runtime_path){
					// 支持文字替换
					self::support( $extendPath );
				}

				self::supportSmartyTpl($extendPath);
			
		}

		// 查找所有的 include 
		if( self::hasTags( $content, 'include') && $includeMatches = self::hasTags( $content, 'include')){

			$i = 0;
			while( isset($includeMatches[$i]) && $includeMatches[$i] !== NULL ){

			$includeFile = self::parseInclude( $includeMatches[$i++] ); 
			$includePath = self::camparePath( $includeFile , $path );

			$runtime_path = Cache::cache($includePath);

			if($runtime_path){
				// 支持文字替换
				self::support( $includePath );
			}

			self::supportSmartyTpl($includePath);

			}
		}

	}

	static private function camparePath($targetPath, $curPath){
		$targetPathArr = explode ('/', $targetPath);
		$curPathArr    = explode ('/', $curPath);

		$targetPathStatus = array_count_values($targetPathArr);

		while(isset($targetPathStatus['..']) && $targetPathStatus['..']--){
			array_pop($curPathArr);
		}

		foreach($targetPathArr as $val){
			if ( $val !== '..' && $val !== '.' ) {
				array_push($curPathArr, $val);
			}
		}

		return implode('/', $curPathArr);

	}

	/**
	 * 判断模板中是否有标签，没有返回false，有则返回标签属性
	 * @access private
	 * @param  $content  模板内容
	 * @param  $tag      标签
	 * @return mix
	 */
	static private function hasTags($content, $tag){
		// 获取smarty配置
		$config     =   BYS::callConfig( 'smarty', 'vender' );

    $begin      =   $config['=']['left_delimiter'];
    $end        =   $config['=']['right_delimiter'];  
    // 读取模板中的继承标签
    $find       =   preg_match_all('/'.$begin.$tag.'\s(.+?)\s*?'.$end.'/is',$content, $matches);

    if($find && count($matches) == 2){
		  return $matches[1];
    }else{
    	return false;
    }
	}


 /**
 * 解析模板中的extend模板继承
 * @access private
 * @param  $content  模板内容
 * @return array
 */
	static private function parseExtend($matches) {
    	$attrs = self::parseXmlAttrs($matches[0]);
	  	return $attrs['file'];
  }

 /**
 * 解析模板中的include
 * @access private
 * @param  $content  模板内容
 * @return array
 */
	static private function parseInclude($matches) {
	   return preg_replace('/[\'|\"]/', '', $matches);
  }


  /**
   * 分析XML属性
   * @access private
   * @param  string $attrs  XML属性字符串
   * @return array
   */
  static private function parseXmlAttrs($attrs) {
      $xml        =   '<tpl><tag '.$attrs.' /></tpl>';
      $xml        =   simplexml_load_string($xml);
      if(!$xml)
         Report::error('路径解析失败');
      $xml        =   (array)($xml->tag->attributes());
      $array      =   array_change_key_case($xml['@attributes']);
      return $array;
  }


}