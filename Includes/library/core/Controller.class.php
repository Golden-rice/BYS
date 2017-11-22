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
   */
  public function __call($method, $args) {
    echo "无{$method}方法，其参数是:";
    var_dump($args);
    // if(method_exists($this,'_empty')) {
    //     // 如果定义了_empty操作 则调用
    //     $this->_empty($method,$args);
    // }elseif(file_exists_case($this->view->parseTemplate())){
    //     // 检查是否存在默认模版 如果有直接输出模版
    //     $this->display();
    // }
  }


  /**
   * 数据库控制器方法分派
   * 根据传递的参数，将参数传递给某方法
   * @access public
   */
  public function assignAction(){
    // 判断提交方法的方式 POST ? GET ?
    if(REQUEST_METHOD == 'GET' && isset($_GET['action'])){
      $action = $_GET['action'];
    }
    elseif(REQUEST_METHOD == 'POST' && isset($_POST['action'])){
      $action = $_POST['action'];
    }
    else{
      Report::error('请求方法错误，缺少必要参数');
    }
    // 执行该方法
    $this->$action($query);
  }

  /**
   * 数据查询
   * @access public
   */
    /*
      语法：
      "query":
      {
        "conditions":{  
          "dep": "BJS",
          "arr": "MIA",
          "airline": "UA"
        }, 
        "select": ['dep', 'arr'],
        "orderby":[{"column":"gmtCreate","asc":"true"}]
      }
    */
  public function query($modelName = '', $config = array()){
    $m = model($modelName);
    // 必须
    if(isset($config['conditions']))
      // 反序列化
      if(is_string($config['conditions'])){
        $where = json_decode($config['conditions'], true);
      }else{
        $where = $config['conditions'];
      }
    

    if(isset($config['select']))
      // 反序列化
      if(is_string($config['select'])){
        $select = json_decode($config['select'], true);
      }else{
        $select = $config['select'];
      }
    else
      $select = array();

    if(isset($config['orderby']))
      // 反序列化
      if(is_string($config['orderby'])){
        $orderby = json_decode($config['orderby'], true);
      }else{
        $orderby = $config['orderby'];
      }
    else
      $orderby = array();
      
    return $m->find($where, $orderby, $select);
  }


  /**
   * 逐条更新数据
   * @access public
   */
    /*
      语法：
      "update":
      {
        "value":{  
          "dep": "BJS",
          "arr": "MIA",
          "airline": "UA"
        }, 
        "where": ['dep', 'arr']
      }
    */
  public function update($modelName = '', $config = array()){

  }

  /**
   * 批量更新数据
   * @access public
   */
    /*
      语法：
      "updates":[
      {
        "value":{  
          "dep": "BJS",
          "arr": "MIA",          
        }, 
        "where": {
          "airline": "UA"
        }
      },
      {
        "value":{  
          "dep": "BJS",
        }, 
        "where": {
          "airline": "UA",
          "arr": "MIA",
        }
      }]

    */
  public function updates($modelName = '', $config = array()){
    $m = model($modelName);
    if(empty($config)) {
      \BYS\Report::error('数据为空');
      return;
    }
    // 反序列化
    if(is_string($config)){
      $datas = json_decode($config, true);
    }else{
      $datas = $config;
    }

    return $m->updates($datas);
  }
}