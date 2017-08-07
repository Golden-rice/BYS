<?php
namespace BYS;
/** Use 匹配config.php 生成相应方法
 * 
 */
class Drive {
	// 设置
	public $set = array();


	function __construct ($config){
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
   * 替换文本
   * @access public
   * @param  $filePath   模板文件
   */
	public function replaceVarContent($filePath){
		if (!isset($this->set['TPL_VAR'])) return;
		$replace = $this->set['TPL_VAR'];
		return str_replace( array_keys($replace), array_values($replace), file_get_contents($filePath) );
	}
}