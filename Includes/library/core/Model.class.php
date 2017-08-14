<?php
	namespace BYS;
	/**
	 * 模型基类 抽象类
	 */
	 abstract class Model {

	 	// 表单前缀
	 	protected $tablePrefix      =   '';

	 	function __construct($className = ""){
		 	// 声明全局变量
		 	BYS::$_GLOBAL['mod_path'] = BYS::$sitemap['Model'];
		 	// 设置字符格式
		 	Db::$link->query('set names utf8');
		 	// 执行设置
		 	 foreach (Db::$options as $key => $value) {
			 	 Db::$link->setAttribute($key, $value);
		 	 }

		 	// 统一表单名风格
		 	$this->tableName = $className == '' ? '' : $this->tablePrefix.$this->parse_name($className, 0);

	 	}

	 	/**
		 * 创建表单
		 * @param  string $name 查询语句
		 * @param  array  $attr 表字段及属性
		 * @return bool
		 */
	 	public function _creat($name = '', $attr){
	 		$str = "CREATE TABLE `{$name}` \r";
	 		foreach ($attr as $key => $value) {
	 			$str .= "`$key` $value ,\r";
	 		}

	 		Report::p($str);
	 	}


	 	/**
		 * 直接查询
		 * @param  string $sql 查询语句
		 * @return array
		 */
	 	public function query($sql = ''){
	 		if($sql == '') Report::warning('无查询语句');

		 	try{
	        $rows = Db::$link -> query($sql); // 返回类似于数组
	        foreach ($rows as $row) {
	        	$result[] = $row;
	        }
	        return $result;
	    }catch(PDOException $e){
	        echo '错误是：'.$e->getMessage();
	    }

	 	}

	 	/**
		 * 事务预处理
		 * @param  string $sql 查询语句
		 */
	 	public function prepare($sql = ''){
	 		if($sql == '') Report::warning('无查询语句');
		 	try{
	        $this->prepare = Db::$link -> prepare($sql); // 返回类似于数组
	    }catch(PDOException $e){
	        echo '错误是：'.$e->getMessage();
	    }
	 	}

	 	/**
		 * 事务预处理 执行
		 * @param  array $param 执行参数
		 * @return array
		 */
	 	public function execute($param = array()){
	 		if( !isset($this->prepare)) Report::error('缺少预处理');
		 	try{
	        $this->prepare->execute($param);
	        // 生成关联数组
	        return $this->prepare->fetchALL();
	    }catch(PDOException $e){
	        echo '错误是：'.$e->getMessage();
	    }
	 	}

	 	/**
		 * 字符串命名风格转换
		 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
		 * @param string $name 字符串
		 * @param integer $type 转换类型
		 * @return string
		 */
		private function parse_name($name, $type=0) {
		    // #1
		    if ($type) {
		        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function($match){return strtoupper($match[1]);}, $name));
		    } 
		    // #0
		    else {
		        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
		    }
		}
	 }
?>