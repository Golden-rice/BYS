<?php
	namespace BYS;
	/**
	 * 模型基类 抽象类
	 */
	 abstract class Model {

	 	function __construct(){
		 	// 声明全局变量
		 	BYS::$_GLOBAL['mod_path'] = BYS::$sitemap['model'];

	 	}
	 	

	 	
	 }
?>