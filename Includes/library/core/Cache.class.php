<?php
namespace BYS;

/** 
 * 缓存
 */

class Cache {

	// 放置缓存文件夹中的路径
	static $runtime_path = '';

	/** 
	 * 根据路由生成缓存，以当前方法为路由解析路径
	 * @return string 要缓存的文件路径
	 */
	static public function router(){
		self::have_runtime();

		$runtime_path = BYS::$sitemap['runtime'].BYS::$_GLOBAL['app'].'/'.BYS::$_GLOBAL['con'];
		$runtime_tpl  = BYS::$sitemap['runtime'].BYS::$_GLOBAL['app'].'/'.BYS::$_GLOBAL['con'].'/'.BYS::$_GLOBAL['act'].constant('VIEW_EXD');

		if( !is_dir($runtime_path) && mkdir($runtime_path, 0777, true) ) {

    }elseif(!is_file($runtime_tpl)){
        $handle = fopen($runtime_tpl, "w", TRUE ) or die("Unable to open file!");
        fclose($handle);
    }

    Cache::$runtime_path = $runtime_tpl;
    return $runtime_tpl;
	}


	static public function cache($file){
		self::have_runtime();

		$source_tpl = $file;
		$runtime_tpl = str_replace(BYS::$_GLOBAL['view_path'], BYS::$sitemap['runtime'] ,$file);

		if( !is_dir( dirname($runtime_tpl) ) && mkdir(dirname($runtime_tpl), 0777, true) ) {

    }

    if(!is_file($runtime_tpl) && is_file($source_tpl)){
			copy($source_tpl, $runtime_tpl);
    }

    Cache::$runtime_path = $runtime_tpl;
    return $runtime_tpl;
	}

	/** 
	 * 判断是否有缓存路径，如果没有则生成
	 * @return void
	 */
	static public function have_runtime(){
		if( !is_dir(BYS::$sitemap['runtime']) && mkdir(BYS::$sitemap['runtime']) ) {
			Report::log('-> make runtime dir');
		}
	}

}