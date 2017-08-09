<?php
namespace BYS;
/** Use 匹配config.php 生成相应方法
 * 
 */
class Drive {
	// 设置
	public $set = array();

	// 模板
	public $content = '';

	function __construct ($config, $filePath = ''){
		if(!isset($config)) {
			Report::error('没有配置');
			return;
		}

		// 公共配置
		if(isset($config['COMMON'])){
			$this->commoning($config['COMMON']);
		}

		// 各个应用配置
		foreach ($config as $name => $set) {
			if( preg_match("/^APP_(.*)?/", $name, $str) )
				$this->privating($set, $str[1] ? $str[1] : "");
		}

		if(is_file($filePath)) $this->content = file_get_contents($filePath);
	}

	// COMMON 配置应用
	public function commoning($set = array()){
		$this->set = $set; 
	}

	/**
   * APP 配置应用
   * @access public
   * @param  $set      设置
   * @param  $appName  应用名
   * @return void
   */
	public function privating($set = array(), $appName = ""){
		if(BYS::$_GLOBAL['app'] == $appName){
			$this->set = array_merge($this->set, $set); 
		}else{
			$this->commoning($set);
		}
	}

	/**
   * 驱动支持
   * @access public
   * @param  $filePath   模板文件路径
   */
	public function support( $filePath ){
		// 替换文本
		$content = $this->replaceVarContent( $filePath );
		file_put_contents( Cache::$runtime_path , $content);
	}

	/**
   * 替换文本
   * @access public
   * @param  $filePath   模板文件
   * @return string      替换模板后的结果
   */
	public function replaceVarContent($filePath){
		if (!isset($this->set['TPL_VAR']) || !is_file($filePath)) return;
		$replace = $this->set['TPL_VAR'];
		return str_replace( array_keys($replace), array_values($replace), file_get_contents($filePath) );
	}

	/**
   * 引入第三方库
   * @access public
   * @param  $path   路径
   * @return classObject 
   */
	public function import($path){
		if(!is_string($path) || $path == "") return;
		if( is_file(LIB_PATH.$path) ){
			return include LIB_PATH.$path;
		}elseif( is_file(LIB_PATH.$path.'.class.php') ){
			return include LIB_PATH.$path.'.class.php';
		}else{
			Report::error('无该路径的第三方库');
		}
	}

	/**
   * 支持smarty模板引擎的继承功能
   * @access public
   */
	public function supportSmartyTpl($filePath){
		if($filePath && !is_file($filePath)) return;

		$content = file_get_contents($filePath);
		$path    = dirname($filePath);

		// 查找 extend 仅匹配一次
		if( $this->hasTags( $content, 'extends') && $extendMatches = $this->hasTags( $content, 'extends') ){

				$extendFile = $this -> parseExtend( $extendMatches );
				$extendPath = $this -> camparePath( $extendFile , $path );
				$runtime_path = Cache::cache($extendPath);

				if($runtime_path){
					// 支持文字替换
					$this->support( $extendPath );
				}

				$this->supportSmartyTpl($extendPath);
			
		}

		// 查找所有的 include 
		if( $this->hasTags( $content, 'include') && $includeMatches = $this->hasTags( $content, 'include')){

			$i = 0;
			while( isset($includeMatches[$i]) && $includeMatches[$i] !== NULL ){

			$includeFile = $this -> parseInclude( $includeMatches[$i++] ); 
			$includePath = $this -> camparePath( $includeFile , $path );

			$runtime_path = Cache::cache($includePath);

			if($runtime_path){
				// 支持文字替换
				$this->support( $includePath );
			}

			$this->supportSmartyTpl($includePath);

			}
		}

	}

	private function camparePath($targetPath, $curPath){
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
	 * 判断模板中是否还有标签
	 * @access private
	 * @param  $content  模板内容
	 * @param  $tag      标签
	 * @return mix
	 */
	private function hasTags($content, $tag){
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
	private function parseExtend($matches) {
    	$attrs = $this->parseXmlAttrs($matches[0]);
	  	return $attrs['file'];
  }

 /**
 * 解析模板中的include
 * @access private
 * @param  $content  模板内容
 * @return array
 */
	private function parseInclude($matches) {

	   return preg_replace('/[\'|\"]/', '', $matches);
  }


  /**
   * 分析XML属性
   * @access private
   * @param string $attrs  XML属性字符串
   * @return array
   */
  private function parseXmlAttrs($attrs) {
      $xml        =   '<tpl><tag '.$attrs.' /></tpl>';
      $xml        =   simplexml_load_string($xml);
      if(!$xml)
         Report::error('路径解析失败');
      $xml        =   (array)($xml->tag->attributes());
      $array      =   array_change_key_case($xml['@attributes']);
      return $array;
  }


}