<?php

/**
 * 引入第三方库
 * @access public
 * @param  $path   路径
 * @return classObject 
 */
function import($path){
	if(!is_string($path) || $path == "") return;
	if( is_file(LIB_PATH.$path) ){
		return include LIB_PATH.$path;
	}elseif( is_file(LIB_PATH.$path.'.class.php') ){
		return include LIB_PATH.$path.'.class.php';
	}else{
		Report::error('无该路径的第三方库');
	}
}