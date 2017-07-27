<?php
namespace BYS;

class App {
	static public $siteMap = array();

	static public function test(){
		echo "Class App config success!";
	}

	/** 
	 * 初始化应用
	 * @access public 
	 * @param  array $app      应用及相关信息
   * @return void
	 */
	static public function init($app){
		// 初始化内容

	}

	/** 
	 * 生成应用
	 * @access public 
	 * @param  array $app      应用及相关信息
   * @return void
	 */
	static public function build($app){
		// 文件夹
		switch ($app['type']) {
			case 0: 
				$siteMap = array(
					$app['name'] => APP_PATH.$app['name']."/",
					"model"      =>APP_PATH.$app['name']."/model/",
					"view"       =>APP_PATH.$app['name']."/view/",
					"ctroller"   =>APP_PATH.$app['name']."/ctroller/",
					"drive"      =>APP_PATH.$app['name']."/drive/",
				);
				break;
			default:
				$siteMap = array(
					$app['name'] => APP_PATH.$app['name']."/",
					"model"      =>APP_PATH.$app['name']."/model/",
					"view"       =>APP_PATH.$app['name']."/view/",
					"ctroller"   =>APP_PATH.$app['name']."/ctroller/",
					"drive"      =>APP_PATH.$app['name']."/drive/",
				);
				break;
		}
		self::$siteMap = $siteMap;
		self::appendBlank(APP_PATH);

		foreach ($dir as $dirName) {
			if ( !is_dir($dirName) ) mkdir($dirName, 0755, true);
			self::appendBlank($dirName);
		}

		

		self::init($app);
	}

	/** 
	 * 生成空白页
	 * @access private 
	 * @param  string $dirName     目录名
   * @return void
	 */
	static private function appendBlank($dirName){
		if( !is_file($dirName."index.html") ) file_put_contents($dirName."index.html", "");
	}
}