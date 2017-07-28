<?php

namespace BYS;
/**
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 * URL 匹配模式： App/Module/Controller/Action
 */
class Dispatcher {

  /**
   * URL映射到控制器
   * @access public
   * @return void
   */
	static public function dispatch(){
		Report::p($_SERVER); // ['PATH_INFO']
		$url = array(
			$_SERVER['HTTP_HOST'] => array()
		);
		// [DOCUMENT_URI] => /dm.eterm/index.php
		// [REQUEST_URI]  => /dm.eterm/
		// [SCRIPT_NAME]  => /dm.eterm/index.php
		// [PHP_SELF] => /dm.eterm/index.php
	}
	
}