<?php

/**
 * 获取当前的App的Model类，生成相应表对象，
 * @param string $name 资源地址，格式：[模块/]模型
 * @return BYS\Model
 */
function model($name='', $level = 1) {
    // if(empty($name)) BYS\Report::error('没有该模型');

    if(strpos($name,'/') && substr_count($name, '/')>=$level){ 
        list($app, $className) =  explode('/',$name, 2);
    }else{
        $app =    BYS\BYS::$_GLOBAL['app'] ;
        $className = parse_name($name, 1);
    }

    import( $className.'Model.class.php', APP_PATH.$app.'/Model/');

    // 实例化 
    $class = "{$app}\Model\\".$className.'Model';

    // 相对路径
    if(class_exists($class)) {
        $model      =   new $class($className);
    }else {
        BYS\Report::error('无该模型');
        $model      =   false;
    }
    return $model;
}


/**
 * 引入库
 * @access public
 * @param  $path       路径
 * @param  $basePath   路径指向
 * @return classObject 
 */
function import($path, $basePath = ""){
    if(!is_string($path) || $path == "") return;

    if($basePath == ''){
        // 引入第三方文件
        $basePath = LIB_PATH;
    }

    if( is_file($basePath.$path) ){
        return include_once $basePath.$path;
    }elseif( is_file($basePath.$path.'.class.php') ){
        return include_once $basePath.$path.'.class.php';
    }else{
        BYS\Report::error('无该路径的库');
    }
}

/**
 * 使用其他类的方法
 * @access public
 * @param  $module       模块名
 * @param  $controller   方法
 * @return classObject 
 */
function reflect($controller, $module = 'Controller'){
    if(!$controller) return;

    $localfiles = substr($_SERVER['SCRIPT_FILENAME'], 0, -9);
    $path = "app/".\BYS\BYS::$_GLOBAL['app']."/{$module}/".$controller."{$module}.class.php";
    if(is_file($localfiles.$path))
        include_once $localfiles.$path;
    else
        echo 'no file reflect target;';
    $class = BYS\BYS::$_GLOBAL['app']."\\{$module}\\".ucfirst($controller).$module;
    return new $class;
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格 (驼峰输入法)
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type=0) {
    // #1
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function($match){return strtoupper($match[1]);}, $name));
    } 
    // #0
    else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}
