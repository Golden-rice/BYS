<?php
namespace BYS;
/**
 * 控制器基类 抽象类
 */
abstract class Controller {

	// 重构控制器类
  public function __construct() {
    // Hook::listen('action_begin',$this->config);

    // 实例化BYS视图类
    // $this->viewObject = BYS::instance('BYS\View');

    // 实例化Smarty类
    $this->smarty = BYS::callVender( 'Smarty',  BYS::callConfig( 'smarty', 'vender' ) );
   
    // 应用的驱动
    $this->drive = BYS::callClass( 'Drive', BYS::callConfig() );

  }

  // 
  /**
   * 扩展smarty的display方法: 为空则以当前方法位命名
   * @access protected
   * @param  string $tpl 模板名
   */
  protected function display($tpl = ""){

    // 模板后缀
    if(!preg_match('/\.html$/', $tpl))
      $ext = constant('VIEW_EXD');
    else
      $ext = "";

    $default_path = BYS::$_GLOBAL['view_path'];
    $default_app  = BYS::$_GLOBAL['app'];
    $default_con  = BYS::$_GLOBAL['con'];
    $default_act  = BYS::$_GLOBAL['act'];

    // 模板初始地址为以APP命名为准
    $base_path   = "{$default_path}{$default_app}/";
    $tpl         = "{$base_path}{$tpl}";
    
    // 默认模板路径
    if($tpl == "" ){

      $default_tpl = "{$base_path}{$default_con}/{$default_act}";
      $runtime_path = "{$default_app}/{$default_con}/";

      $tpl = $default_tpl;
    }
    
    if( is_file($tpl.$ext) ) {

      $content = $this->drive->replaceVarContent( $tpl.$ext );
      $runtime_path = isset($runtime_path) ? BYS::$sitemap['runtime'].$runtime_path : $tpl;
      $runtime_tpl = $runtime_path.$default_act.$ext;

      if( !is_file($runtime_tpl) && mkdir($runtime_path, 0777, true) ) {
        $handle = fopen($runtime_tpl, "w", TRUE ) or die("Unable to open file!");
        fclose($handle);
      }
      
      file_put_contents( $runtime_tpl, $content);

      $this->smarty->display( $runtime_tpl );
    }else{
      Report::error('没有模板');
    }
  }

  /**
   * 扩展smarty的assign方法: 为空则以当前方法位命名
   * @access protected
   */
  protected function assign($name, $var){
    $this->smarty->assign($name, $var);
  }

  /**
   * 魔术方法：有不存在的操作的时候执行
   * @access public
   * @param  string $method 方法名
   * @param  array  $args   参数
   * @return mixed
   */
  public function __call($method, $args) {
      if( 0 === strcasecmp($method, ACTION_NAME.C('ACTION_SUFFIX'))) {
          if(method_exists($this,'_empty')) {
              // 如果定义了_empty操作 则调用
              $this->_empty($method,$args);
          }elseif(file_exists_case($this->view->parseTemplate())){
              // 检查是否存在默认模版 如果有直接输出模版
              $this->display();
          }else{
              E(L('_ERROR_ACTION_').':'.ACTION_NAME);
          }
      }else{
          E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
          return;
      }
  }



}