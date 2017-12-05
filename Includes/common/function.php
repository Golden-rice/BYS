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
    }
    return $model;
}

/**
 * 切换链接
 * @param string $configName 连接的配置名
 */
function connect($configName){
    $config = BYS\BYS::callConfig();
    if(isset($config['COMMON']['DB_CONFIG_LIST'][$configName]) && $dbConfig = $config['COMMON']['DB_CONFIG_LIST'][$configName]){
        BYS\Db::connect($dbConfig);
    }else{
        BYS\Report::error('无该数据库配置项');
    }
}

/**
 * 恢复原链接
 */
function reset_connect(){
    $config = BYS\BYS::callConfig();
    if(isset($config['COMMON']['DB_CONFIG_LIST']['DEFAULT']) && $dbConfig = $config['COMMON']['DB_CONFIG_LIST']['DEFAULT']){
        BYS\Db::connect($dbConfig);
    }else{
        BYS\Report::error('无该数据库配置项');
    }
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
 * 生成cookie，解决不同控制器无法共享cookie的问题
 * @access public
 * @param  string $name      cookie名
 * @param  string $value     cookie值
 * @param  mix    $options   配置
 */
function cookie($name = '', $value = '', $options = null){
    // 设置cookie
    // 默认设置
    $config = array(
        'expire'    =>  0,     // cookie 保存时间
        'path'      =>  '/',   // cookie 保存路径
        'domain'    =>  '',    // cookie 有效域名
        'secure'    =>  false, // cookie 启用安全传输
        'httponly'  =>  '',    // httponly设置
    );

    // 用新的设置覆盖
    if(!is_null($options)){
        if(is_numeric($options)) $config['expire'] = $options;
        elseif (is_string($options)) parse_str($options, $options);
        elseif (is_array($options)) $config = array_merge($config, array_change_key_case($options));
        else BYS\Report::error('cookie的参数错误');

        if(isset($options['httponly']) && !empty($options['httponly'])){
            ini_set("session.cookie_httponly", 1);
        }
    }

    // 返回值
    if(empty($name)) return $_COOKIE;
    if($value === '' && !empty($name)) return $_COOKIE[$name];

    // 删除
    if(is_null($value)  && !empty($name)) setcookie($name, $value, null);

    // 设置
    setcookie($name, $value, $config['expire'], $config['path'], $config['domain'], $config['secure'], $config['httponly']);
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
