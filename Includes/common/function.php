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
        $className = $name;
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
        return include $basePath.$path;
    }elseif( is_file($basePath.$path.'.class.php') ){
        return include $basePath.$path.'.class.php';
    }else{
        BYS\Report::error('无该路径的库');
    }
}
