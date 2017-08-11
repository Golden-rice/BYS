<?php

namespace BYS;

class Db {
	// 数据库连接
  static public $link;

	/**
	 * 链接数据库并使用相关设置
   * @param  $config    用户配置
   */
	static public function connect($config){

		if(!is_array($config)) return false;

		// 表单前缀暴露至全局
		BYS::$_GLOBAL['db_prefix'] = isset($config['DB_PREFIX']) ? $config['DB_PREFIX'] : "";
		
		// 生成pdo配置
		$pdoConfig = self::parseConfig($config);
		// 生成pdo的dsn 
		$pdoConfig['dsn'] = self::parseDsn( $pdoConfig );

		
    try{
        if(empty($pdoConfig['dsn'])) {
            $pdoConfig['dsn']  =   self::parseConfig($config);
        }
        if(version_compare(PHP_VERSION,'5.3.6','<=')){ //禁用模拟预处理语句
            $this->options[PDO::ATTR_EMULATE_PREPARES]  =   false;
        }
        self::$link = new \PDO( $pdoConfig['dsn'], $pdoConfig['username'], $pdoConfig['password']);
    }catch (\PDOException $e) {
        Report::error('数据库连接错误');
    }

		return true;
	}

  /**
   * 数据库连接参数解析
   * @static
   * @access private
   * @param mixed $config
   * @return array
   */
	static private function parseConfig( $config=array() ){
		return  array(
        'type'       =>  $config['DB_TYPE'],
        'hostname'   =>  $config['DB_HOST'],
        'database'   =>  $config['DB_NAME'],
        'username'   =>  $config['DB_USER'],
        'password'   =>  $config['DB_PWD'],
        'hostport'   =>  $config['DB_PORT'],
        'charset'    =>  $config['DB_CHARSET'],
			);
	}

	/**
   * 解析pdo连接的dsn信息
   * @access public
   * @param array $config 连接信息
   * @return string
   */
  static private function parseDsn($config){
    $dsn  =   'mysql:dbname='.$config['database'].';host='.$config['hostname'];
    if(!empty($config['hostport'])) {
        $dsn  .= ';port='.$config['hostport'];
    }elseif(!empty($config['socket'])){
        $dsn  .= ';unix_socket='.$config['socket'];
    }

    if(!empty($config['charset'])){
        //为兼容各版本PHP,用两种方式设置编码
        $dsn  .= ';charset='.$config['charset'];
    }
    return $dsn;
	}
}