<?php

	// eterm 核心库
	include 'Eterm.class.php';

	define('ETERM_ROOT', VEND_PATH.'eterm/');

	// 获取 eterm 命令库
	autoload( autoReadCommand(ETERM_ROOT, 'Eterm') );

	/** 
	 * 自动读取command类库
	 * @param  $path       路径
	 * @param  $namespace  命名空间
   * @return void
	 */
	function autoReadCommand($path, $namespace = ""){
		$pathMap = array();

		if( is_dir($path) && $handle = opendir($path) ){
			while( ($file = readdir($handle)) !== false ){
				if( $file!='.' && $file!='..' ){
					preg_match("/^(\w+)\.command\.php/", $file, $name);
					if( isset($name[1]) )  array_push($pathMap, "{$name[1]}.command.php");
				}
			}
		}else{
			// 报错: 没有该类库
			Report::error("没有该类库");
		}
		return $pathMap;
	}

	/** 
	 * 自动加载map中的类库，并返回实例后的类组成的数组
	 * @param  array $map 待加载类名
	 */
	function autoload($map){

		foreach ($map as $file) {
			if( is_file(ETERM_ROOT.$file) ) {
	        include ETERM_ROOT.$file;
	    }
    }
	}