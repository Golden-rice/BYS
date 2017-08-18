<?php
namespace BYS;
/**
 * 控制器基类 抽象类
 */
abstract class Controller {

	// 重构控制器类
  public function __construct() {

    // 实例化BYS视图类
    // $this->viewObject = BYS::instance('BYS\View');

    // 实例化Smarty类
    $this->smarty = BYS::callVender( 'Smarty',  BYS::callConfig( 'smarty', 'vender' ) );
   
    // 应用的驱动
    $this->drive = BYS::callClass( 'Drive', BYS::callConfig() );
    
  }

  /**
   * 扩展smarty的display方法: 为空则以当前方法位命名
   * @access protected
   * @param  string $tpl 模板名
   */
  protected function display($tpl = ""){
    // 模板后缀
    $ext          = preg_match('/\.html$/', $tpl) ? "" : constant('VIEW_EXD');
    $default_path = BYS::$_GLOBAL['view_path'];
    $default_app  = BYS::$_GLOBAL['app'];
    $default_con  = BYS::$_GLOBAL['con'];
    $default_act  = BYS::$_GLOBAL['act'];
    // 模板初始地址为以APP命名为准
    $tpl = $default_tpl = $tpl == "" ? "{$default_path}{$default_app}/{$default_con}/{$default_act}" : "{$default_path}{$default_app}/{$tpl}";
    if( is_file($tpl.$ext) ) {
      $this->drive->supportSmartyTpl($tpl.$ext);
      // 生成路由缓存
      $r = Cache::router();
      // 执行驱动
      $this->drive->support( $tpl.$ext );
      
      $this->smarty->display( $r );
    }else{
      echo $tpl.$ext;
      Report::error('没有模板');
    }
  }

  /**
   * 扩展smarty的assign方法: 为空则以当前方法位命名
   * @access protected
   */
  protected function assign($name, $var){
    return $this->smarty->assign($name, $var);
  }

  /**
   * 魔术方法：有不存在的操作的时候执行
   * @access public
   * @param  string $method 方法名
   * @param  array  $args   参数
   * @return mixed
   */
  public function __call($method, $args) {
    if(method_exists($this,'_empty')) {
        // 如果定义了_empty操作 则调用
        $this->_empty($method,$args);
    }elseif(file_exists_case($this->view->parseTemplate())){
        // 检查是否存在默认模版 如果有直接输出模版
        $this->display();
    }
  }


}