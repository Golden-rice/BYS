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
					"runtime"    =>ROOT.'~Runtime/',
					'view'       =>ROOT.'View/'
				);
				break;
			default:
				$siteMap = array(
					$app['name'] =>APP_PATH.$app['name']."/",
					"Model"      =>APP_PATH.$app['name']."/Model/",
					"View"       =>APP_PATH.$app['name']."/View/",
					"Controller" =>APP_PATH.$app['name']."/Controller/",
					"Drive"      =>APP_PATH.$app['name']."/Drive/",
					"runtime"    =>ROOT.'~Runtime/',
					'view'       =>ROOT.'View/'
				);
				break;
		}

		$this->siteMap = $siteMap;

		// 暴露到全局
		BYS::$sitemap = $this->siteMap;
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

		// 确定视图模式路径，默认 public 模式
		if ( constant('VIEW_TYPE') == 'private' ){
			BYS::$_GLOBAL['view_path'] = BYS::$_GLOBAL['app'].'View/';
		}else{
			BYS::$_GLOBAL['view_path'] = ROOT.'View/';
		}

		// 启用控制器
		if( isset(BYS::$_GLOBAL['con_path']) && BYS::$_GLOBAL['con_path'] != null){
			self::activeController();
		}else{
			Report::error("无控制器");
		}



		// 安全过滤 $_GET $_POST $_REQUEST

		// URL调度结束标签
    // Hook::listen('url_dispatch'); 
	}

	/** 
	 * 执行控制器方法
	 * @access private 
	 */
	static public function activeController(){
		include_once constant('AB_CONTROLLER_PATH');
		include_once BYS::$_GLOBAL['con_path'];
		
		$controller = BYS::$_GLOBAL['app']."\\Controller\\".BYS::$_GLOBAL['con'].'Controller';

		// 方法
		if(class_exists($controller)) self::invokeControllerAction(new $controller, BYS::$_GLOBAL['act']);

	}

	/** 
	 * 执行控制器方法
	 * @access public 
	 * @param  string $controller  控制器名
	 * @param  string $action      动作名
   * @return void
	 */
	static public function invokeControllerAction($controller, $action){
		if(!preg_match('/^[A-Za-z](\w)*$/',$action)){
    	// 非法操作
    	throw new \ReflectionException();
    }

    //执行当前操作
    $method =   new \ReflectionMethod($controller, $action);
    if($method->isPublic() && !$method->isStatic()) {
    	$class  =   new \ReflectionClass($controller);

    	// 带参数
	    if($method->getNumberOfParameters()>0){
	    	$params =  $method->getParameters();
    	  foreach ($params as $param){
  				$name = $param->getName();
  				if( !empty($vars) ){
  					$args[] =   array_shift($vars);
  				}elseif( isset($vars[$name]) ){
  					$args[] =   $vars[$name];
  				}elseif($param->isDefaultValueAvailable()){
  					$args[] =   $param->getDefaultValue();
  				}else{
  					Report::error('无参数:'.$name);
  				}   
  			}
	    	$method->invokeArgs($controller, $args);
	    }else{
	    	$method->invoke($controller);
	    }
    }else{
    		// 操作方法不是Public 抛出异常
    		throw new \ReflectionException();
    	}
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
	 * @access public 
	 * @param  string $module       模块名
	 * @param  string $controller   控制器名
   * @return void
	 */
	static public function buildModule($app, $module, $controller = 'Index'){
		$file = APP_PATH.$app['name']."/$module/Index$module.class.php";
		if ( !is_file($file) ){
			$content = str_replace(array('[MODULE]','['.strtoupper($module).']'), array($app['name'], $controller),self::$$module);
			$dir = dirname($file);
	    if(!is_dir($dir)){
	      mkdir($dir, 0755, true);
	    }
	    file_put_contents($file, $content);
    }
	}
}