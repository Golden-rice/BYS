<?php
namespace BYS;
/**
 * 控制器基类 
 */
abstract class Controller {
  protected $model;

	// 重构控制器类
  public function __construct() {

    // 实例化BYS视图类
    // $this->viewObject = BYS::instance('BYS\View');

    // 实例化Smarty类
    $this->smarty = BYS::callVender( 'Smarty',  BYS::callConfig( 'smarty', 'vender' ) );
   
    // 装载应用的驱动
    // $this->drive = BYS::callClass( 'Drive', BYS::callConfig() );
    Drive::init(BYS::readConfig( BYS::$default ));
  }

  // 当尝试以调用函数的方式调用一个对象时
  public function __invoke(){}

  /**
   * 扩展smarty的display方法: 为空则以当前方法位命名
   * @access protected
   * @param  string $tpl 模板名
   */
  protected function display($tpl = ""){
    // 设置语言
    header('Content-Type:text/html; charset=utf-8');

    // 模板后缀
    $ext          = preg_match('/\.html$/', $tpl) ? "" : constant('VIEW_EXD');
    $default_path = BYS::$_GLOBAL['view_path'];
    $default_app  = BYS::$_GLOBAL['app'];
    $default_con  = BYS::$_GLOBAL['con'];
    $default_act  = BYS::$_GLOBAL['act'];
    // 模板初始地址为以APP命名为准
    $tpl = $default_tpl = $tpl == "" ? "{$default_path}{$default_app}/{$default_con}/{$default_act}" : "{$default_path}{$default_app}/{$tpl}";
    if( is_file($tpl.$ext) ) {
      // $this->drive->supportSmartyTpl($tpl.$ext);
      Drive::supportSmartyTpl($tpl.$ext);
      // 生成路由缓存
      $r = Cache::router();
      // 执行驱动
      // $this->drive->support( $tpl.$ext );
      Drive::support( $tpl.$ext );
      $this->smarty->display( $r );
    }else{
      Report::error($tpl.$ext.'没有模板');
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
    Report::error("无{$method}方法，其参数是:".var_dump($args));
    // if(method_exists($this,'_empty')) {
    //     // 如果定义了_empty操作 则调用
    //     $this->_empty($method,$args);
    // }elseif(file_exists_case($this->view->parseTemplate())){
    //     // 检查是否存在默认模版 如果有直接输出模版
    //     $this->display();
    // }
  }


  /*
    语法（隐式）：
    "query":
    {
      "modelName": 'table_name',
      config: {
        "conditions":{  
          "dep": "BJS",
          "arr": "MIA",
          "airline": "UA"
        }, 
        "select": ['dep', 'arr'],
        "orderby":[{"name":"asc"}]
      }
    }
    语法（显式）：
    action: "query"
  */
  /**
   * 控制器方法分派
   * 根据传递的参数，将参数传递给某方法
   * @access public
   */
  public function assignAction(){
    // 判断提交方法的方式 POST ? GET ?
    if(REQUEST_METHOD == 'GET')
      $var = $_GET;    
    elseif(REQUEST_METHOD == 'POST')
      $var = $_POST;
    else
      Report::error('请求方法错误，缺少必要参数 From Controller::assignAction');
    
    // 显示执行方法
    if(isset($var['action']) && $action = $var['action']){
      $this->$action();
    }
    // 隐式执行方法
    else{
      if(count($var) === 1 && current($var)){
        $action     = key($var);
        $controller = BYS::$_GLOBAL['app']."\\Controller\\".BYS::$_GLOBAL['con'].'Controller';
        $args       = $var[$action];
        if(class_exists($controller)){
          if(method_exists($controller, $action)){
            if(!empty($args)){
              $controller = new $controller;
              $method     = new \ReflectionMethod($controller, $action);
              $method->invokeArgs($controller, $args);
            }
          }
          else
            Report::error("{$_GLOBAL['con']}无{$action}方法 From Controller::assignAction");
        }
        else
          Report::error("无{$_GLOBAL['con']}类 From Controller::assignAction");
      }else
        Report::error('请求方法错误，使用隐式请求时，参数必须为一个 From Controller::assignAction');
    }

  }

  // 根据 name 解析 config
  private function parseConfig($config, $name, $isMust = false){
    // 条件
    if(isset($config[$name]))
      // 反序列化
      if(is_string($config[$name])){
        return json_decode($config[$name], true);
      }else{
        return $config[$name];
      }
    else{
      if( $isMust ){
        Report::error("缺少{$name} From Controller::update");
      }
      return array();
    }
  }

  // 根据查询结果设置返回值
  protected function setReturn( $result, $return = false ){
    if($return){
      // 不做数据筛选，返回查询结果
      return $result;
      // 当发生数据检查是，无数据则插入，这种时候总汇提示
      // else Report::log("{$modelName} 查询无数据");
    }
    else{
      if($result){
        echo json_encode(array('result'=>$result, 'status'=>1, 'msg'=>Report::printLog()));
      }
      else{
        echo json_encode(array('result'=>$result, 'status'=>0, 'msg'=>Report::printLog()));
      }
    }
  }

  /**
   * 数据查询
   * @access public
   */
  public function query($modelName = '', $config = array(), $return = false){
    $m = model($modelName);
    $this->model = $m;
    // 条件
    $where    = $this->parseConfig( $config, 'conditions' );
    // 去重字段
    $distinct = $this->parseConfig( $config, 'distinct' );
    // 查询字段
    $select   = $this->parseConfig( $config, 'select' );
    // 排序
    $orderby  = $this->parseConfig( $config, 'orderby' );
    // 限制数据量
    if(isset($config['limit']))
      $limit = $config['limit'];
    else
      $limit = 1000;

    $result = $m->find($where, $orderby, $select, $distinct, $limit);

    return $this->setReturn($result, $return);
  }


  /**
   * 更新数据
   * @access public
   */
    /*
      语法（隐式）：
      "update":
      {
        "model": "",
        "config": {
          "conditions": {
            "Id": 1
          }
          "values":{  
            "dep": "BJS",
            "arr": "MIA",
            "airline": "UA"
          }, 
        }
      }
    */
  public function update($modelName = '', $config = array(), $return = false){
    $m = model($modelName);

    // 清空
    $m->reset();
    // 条件 必须
    $where    = $this->parseConfig( $config, 'conditions', true );
    // 更新值
    $values   = $this->parseConfig( $config, 'values', true );

    $result = $m->update($values, $where, false);

    return $this->setReturn($result, $return);
  }

  /**
   * 批量更新多条条件的数据
   * @access public
   */
    /*
      语法：
      "updates":{
        "model": '',
        "config": [{
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
      }

    */
  public function updates($modelName = '', $config = array(), $return = true){
    if(empty($config)) 
      return \BYS\Report::error('数据为空 From Controller::updates');

    $m = model($modelName);
    // 清空
    $m->reset();
    // 反序列化
    if(is_string($config)){
      $datas = json_decode($config, true);
    }else{
      $datas = $config;
    }

    $result = $m->updates($datas);
    
    return $this->setReturn($result, $return);
  }

  /**
   * 删除数据
   * @access public
   */
    /*
      语法（隐式）：
      "delete":
      {
        "model": "",
        "config": {
          "where": {
            "Id": 1
          }
        }
      }
    */
  public function delete($modelName = '', $config = array(), $return = false){
    $m = model($modelName);
    // 清空
    $m->reset();
    // 条件 必须
    $where   = $this->parseConfig( $config, 'conditions', true );

              $m->setWhere($where);
    $result = $m->delete();

    return $this->setReturn($result, $return);
  }

  /**
   * 新增数据
   * @access public
   */
    /*
      语法（隐式）：
      "add":
      {
        "model": "",
        "config": {
          "values": [{
            "Id": 1,
            "Name": 'ZS'
          },
          {
            "Id": 2,
            "Name": 'LS'
          }]
        }
      }
    */
  public function add($modelName = '', $config = array(), $return = false){
    $m = model($modelName);
    // 清空
    $m->reset();
    // 必须
    if(isset($config['values']))
      // 反序列化
      if(is_string($config['values'])){
        $values = json_decode($config['values'], true);
      }else{
        $values = $config['values'];
      }
    else
      Report::error('缺少新增的数据 From Controller::add');

    $result = $m->addAll($values);
    
    return $this->setReturn($result, $return);
  }


  // 重复上一次SQL动作
  public function reActSQL(){
    if( $this->model )
      return $this->model->lastSQL(true);
    return false;
  }
}