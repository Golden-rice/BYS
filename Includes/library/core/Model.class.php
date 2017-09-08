<?php
	namespace BYS;
	/**
	 * 模型基类 抽象类
	 */
	 abstract class Model {

	 	// 表单前缀
	 	protected $tablePrefix      =   '';
	 	// 自动添加数据
	 	protected $_validate        = array();
	 	// WHERE
	 	protected $where            = '';
	 	// JOIN
	 	protected $join             = '';
	 	// 当发生失败时，返回 sql 语句
	 	protected $sql              = '';
	 	// 去重语句
		protected $distinct         = '';
		// 排序语句
		protected $order            = '';

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

	 		$str = "CREATE TABLE IF NOT EXISTS `{$name}` ( \r";
	 		foreach ($attr as $key => $value) {
	 			$str .= "`$key` $value ,\r";
	 		}
	 		$str .= "PRIMARY KEY (`Id`)\r) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	 		// Report::p($str);
	 		$this->prepare($str);
	 		$this->execute();
	 	}

	 	/**
		 * 新增数据，返回最后添加的id
		 * @param  array  $data 新增的数据
		 * @return int    
		 */	 	
	 	public function add($data=array()){
	 		// 单条新增
	 			$sql = '';

	 			$sql = "INSERT INTO {$this->tableName} (";
			 	$val = 'VALUES(';
	 			foreach ($data as $key => $value) {
	 				$sql .= "`{$key}`,";
	 				$val .= is_string($value)? "'$value'," : $value.',';
	 			}
	 			if(count($this->_validate) >0){
	 				foreach ($this->_validate as $key => $value) {
	 					$sql .= "`{$key}`,";
	 					if($value == 'TIME'){
	 						$val .= time().',';
	 					}else{
		 					$val .= is_string($value)? "'$value'," : $value.',';
	 					}
	 				}
	 			}
	 			$sql = rtrim($sql, ',').') '.rtrim($val, ',').')';
				$this->exec($sql);
				$lastId = Db::$link->lastInsertId();
				return $lastId;
	 	}

	 	/**
		 * 新增多条数据
		 * @param  array  $data 新增的数据
		 * @return bool 
		 */	 
	 	public function addAll($data=array()){
	 		if (count($data) <=0) return;
 			$sql = '';
 			foreach ($data as $index => $item) {

 				$sql .= "INSERT INTO {$this->tableName} (";
 				$val = 'VALUES(';
 				foreach ($item as $key => $value) {
	 				$sql .= "`{$key}`,";
	 				$val .= is_string($value)? "'$value'," : (int)$value.',';
	 			}

				if(count($this->_validate) >0){
	 				foreach ($this->_validate as $key => $value) {
	 					$sql .= "`{$key}`,";
	 					if($value == 'TIME'){
	 						$val .= time().',';
	 					}else{
		 					$val .= is_string($value)? "'$value'," : (int)$value.',';
	 					}
	 				}
	 			}		
	 			$sql = rtrim($sql, ',').') '.rtrim($val, ',').'); '; 			
 			}

 			$this->prepare($sql);
 			$this->execute();
	 	}

	 	/**
		 * 清空表
		 */	
	 	public function deleteAll(){
 			$this->prepare("TRUNCATE {$this->tableName}");
 			$this->execute();
	 	}


	 	/**
		 * 查询数据的筛选条件
		 * @param  string $where 筛选语句
		 * @return thisObject
		 */	
	 	public function where($where){
	 		if(!is_string($where)) return $this;
	 		if($where != '')
				$this->where = ' WHERE '.$where;
			else
				$this->where = '';
			return $this;
	 	}

	 	public function join($type, $sql){
	 		if(!is_string($type) || !is_string($sql)) return $this;

	 		switch ($type) {
	 			case 'INNER':
			 		$this->join = ' INNER JOIN '.$sql;
		 			break;
	 			case 'LEFT':
		 			$this->join = ' LEFT JOIN '.$sql;
	 				break;
	 			case 'RIGHT':
	 				$this->join = ' RIGHT JOIN '.$sql;
	 				break;
	 			case 'FULL':
	 				$this->join = ' FULL JOIN '.$sql;
	 				break;
	 			default:
	 				$this->join = '';
	 				break;
	 		}
	 		return $this;
	 	}

	  /**
		 * 当返回失败时，查看sql语句
		 * @return string
		 */	 	
	  public function testSql(){
	  	if($this->sql != '')
	  		return $this->sql;
	  	else
	  		return 'No Sql';
	  }

	  /**
		 * 去重
		 */	
	  public function distinct($distinct = ''){
	  	if ($distinct != '')
		  	$this->distinct = ' DISTINCT '.$distinct;
		  else 
		  	$this->distinct = '';
	  	return $this;
	  }

	  /**
		 * 排序
		 */		  
	  public function order($order){
	  	if ($order != '')
		  	$this->order = ' ORDER BY '.$order;
		  else 
		  	$this->order = '';
	  	return $this;
	  }

	  /**
		 * 查询数据
		 * @return mix 结果
		 */	 	
	 	public function select($cols = ''){
	 		$sql = '';

	 		if( $cols != '')
	 			$sql = "SELECT {$cols} ";
		 	else
		 		$sql = "SELECT * ";

		 	if( $this->distinct != '')
		 		$sql = "SELECT {$this->distinct} ";

		 	$sql .= " FROM ".$this->tableName.$this->join.$this->where.$this->order;

	 		$this->prepare($sql);
	 		$result = $this->execute();
	 		if(is_bool($result) && $result == false)
	 			$this->sql = $sql;

	 		// 如果有结果均按数组返回
	 		if(is_array($result) && isset($result[0])){
		 		return $result;
	 		}elseif(is_array($result)){
	 			return array(0=>$result);
	 		}

	 		return $result;
	 	}

	 	/**
		 * 准备查询数据
		 * @return mix 结果
		 */
	 	public function preSelect(){
	 		$sql = "SELECT * FROM ".$this->tableName.$this->where;
	 		$this->prepare($sql);
	 	}

	 	/**
		 * 更新一条数据
		 * @return mix 结果
		 */	 	
	 	public function update($data = array()){
	 		if (count($data) <=0) return;
 			$sql = "UPDATE {$this->tableName} SET ";
 			foreach ($data as $attr => $val) {
 				$sql .= "`{$attr}` = ".(is_string($val)? "'{$val}'" : (int)$val).',';
 			}
	 		$sql = rtrim($sql, ',').$this->where;
	 		$this->prepare($sql);
	 		$this->execute();
	 		return $this->prepare->rowCount();
	 	}


	 	/**
		 * 删除数据
		 * @return mix 结果
		 */	 	
	 	public function delete(){
 			$sql = "DELETE FROM {$this->tableName} ";

	 		$sql = $sql.$this->where;

	 		$this->prepare($sql);
	 		$this->execute();
	 		return $this->prepare->rowCount();
	 	}

	 	/**
		 * 直接query查询: SELECT
		 * @param  string $sql 查询语句
		 * @return array
		 */
	 	public function query($sql = ''){
	 		if($sql == '') Report::warning('无查询语句');

		 	try{
		 			// Db::$link->beginTransaction(); 
	        $rows = Db::$link->query($sql); // 返回类似于数组
	        // Db::$link->commit();
	        $result = array();

	        foreach ($rows as $row) {
	        	$result[] = $row;
	        }

	        return $result;

	    }catch(PDOException $e){
	    		Db::$link->rollback();
	        echo '错误是：'.$e->getMessage();
	    }

	 	}

	 	/**
		 * 直接exec查询: INSERT
		 * @param  string $sql 查询语句
		 * @return array
		 */
	 	public function exec($sql = ''){
	 		if($sql == '') Report::warning('无查询语句');

		 	try{
		 			// Db::$link->beginTransaction(); 
	        $rows = Db::$link -> exec($sql); // 返回类似于数组
	        // Db::$link->commit();

	    }catch(PDOException $e){
	    		Db::$link->rollback();
	        echo '错误是：'.$e->getMessage();
	    }

	 	}
	 	/**
		 * 事务预处理：SELECT 
		 * @param  string $sql 查询语句
		 */
	 	public function prepare($sql = ''){
	 		if($sql == '') Report::warning('无查询语句');
		 	try{
	        $this->prepare = Db::$link -> prepare($sql); // 返回类似于数组
	    }catch(PDOException $e){
	    		Db::$link->rollback();
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

	        if($this->prepare->rowCount() > 1)
	        	return $this->prepare->fetchALL();
	        else
	        	return $this->prepare->fetch();
	    }catch(PDOException $e){
	    		Db::$link->rollback();
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