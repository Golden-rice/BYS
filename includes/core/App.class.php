<?php
namespace BYS;

class App {
	public $siteMap = array();
	static public 	$Controller   =   '<?php
namespace [MODULE]\Controller;
use BYM\Controller;
class [CONTROLLER]Controller extends Controller {
    public function index(){
        $this->show(\'<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>\',\'utf-8\');
    }
}';

  static public   $Model         =   '<?php
namespace [MODULE]\Model;
use BYM\Model;
class [MODEL]Model extends Model {

}';

	/** 
	 * 生成文件目录
	 * @access public 
	 * @param  array $app      应用及相关信息
   * @return void
	 */
	public function __construct($app){
		// 文件夹
		switch ($app['type']) {
			case 0: 
				$siteMap = array(
					$app['name'] =>APP_PATH.$app['name']."/",
					"Model"      =>APP_PATH.$app['name']."/Model/",
					"View"       =>APP_PATH.$app['name']."/View/",
					"Controller" =>APP_PATH.$app['name']."/Controller/",
					"Drive"      =>APP_PATH.$app['name']."/Drive/",
				);
				break;
			default:
				$siteMap = array(
					$app['name'] =>APP_PATH.$app['name']."/",
					"Model"      =>APP_PATH.$app['name']."/Model/",
					"View"       =>APP_PATH.$app['name']."/View/",
					"Controller" =>APP_PATH.$app['name']."/Controller/",
					"Drive"      =>APP_PATH.$app['name']."/Drive/",
				);
				break;
		}
		$this->siteMap = $siteMap;
	}

	/** 
	 * 初始化应用：生成相应模块及初始化文件
	 * @access public 
	 * @param  array $app      应用及相关信息
   * @return void
	 */
	public function init($app){
		// 初始化内容
		$is_right_build = false;


		foreach ($this->siteMap as $module => $dirName) {
			if ( is_dir($dirName) && $module == "Controller") {
				self::buildModule($app, 'Controller', "Index");
				continue;
			}
			if ( is_dir($dirName) && $module == "Model") {
				self::buildModule($app, 'Model', "Index");
			}
		}
	}

	/** 
	 * 生成应用
	 * @access public 
	 * @param  array $app      应用及相关信息
   * @return void
	 */
	public function build($app){
		$this->appendBlank(APP_PATH);

		foreach ($this->siteMap as $dirName) {
			if ( !is_dir($dirName) ) mkdir($dirName, 0755, true);
			$this->appendBlank($dirName);
		}

		$this->init($app);
	}

	/** 
	 * 运行应用
	 * @access public 
	 * @param  array $app      应用及相关信息
   * @return void
	 */
	static public function run($app = array()){
		// 启用路由
		Dispatcher::dispatch();

		// 安全过滤 $_GET $_POST $_REQUEST

		// URL调度结束标签
    // Hook::listen('url_dispatch'); 
	}

	/** 
	 * 生成空白页
	 * @access private 
	 * @param  string $dirName     目录名
   * @return void
	 */
	private function appendBlank($dirName){
		if( !is_file($dirName."index.html") ) file_put_contents($dirName."index.html", "");
	}

	/** 
	 * 生成模块
	 * @access private 
	 * @param  string $module       模块名
	 * @param  string $controller   控制器名
   * @return void
	 */
	static public function buildModule($app, $module, $controller = 'Index'){
		$file = APP_PATH.$app['name']."/$module/Index$module.class.php";
		// echo '[MODULE]'.'['.strtoupper($module).']'.$module;
		// var_dump(str_replace(array('[MODULE]','['.strtoupper($module).']'), array($app['name'], $controller), self::$$module) ); // self::$Controller self::$$module
		// var_dump(str_replace(array('[MODULE]','['.strtoupper($module).']'), array($app['name'], $controller), self::$$module));	
		if ( !is_file($file) ){
			$content = str_replace(array('[MODULE]','['.strtoupper($module).']'), array($app['name'], $controller),self::$$module);
			$dir = dirname($file);
	    if(!is_dir($dir)){
	      mkdir($dir, 0755, true);
	    }
	    file_put_contents($file,$content);
    }
	}
}