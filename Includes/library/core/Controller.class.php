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
    $this->smarty = BYS::callVender( 'Smarty',  BYS::callConfig( 'smarty', 'vender' ));
    // func_get_args()
  }

  // 
  /**
   * 扩展smarty的display方法: 为空则以当前方法位命名
   * @access public
   * @param  string $tpl 模板名
   */
  protected function display($tpl = ""){

    $basis_path = BYS::$_GLOBAL['view_path'];
    $basis_app  = BYS::$_GLOBAL['app'];
    $basis_con  = BYS::$_GLOBAL['con'];
    $basis_act  = BYS::$_GLOBAL['act'];

    if($tpl != "" && is_file($tpl)) 
      $this->smarty->display($tpl);
    elseif( is_file("$basis_path/$basis_app/$basis_con/$basis_act") )
      $this->smarty->display( "$basis_path/$basis_app/$basis_con/$basis_act" );
    else
      Report::error('没有模板');
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