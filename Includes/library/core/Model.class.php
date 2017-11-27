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
		// 限制
		protected $limit            = '';
		// 分组
		protected $group            = '';

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

	 	// 清空所有的属性
	 	public function reset(){
		 	// WHERE
		 	$this->where            = '';
		 	// JOIN
		 	$this->join             = '';
		 	// 当发生失败时，返回 sql 语句
		 	$this->sql              = '';
		 	// 去重语句
			$this->distinct         = '';
			// 排序语句
			$this->order            = '';
			// 限制
			$this->limit            = '';
			// 分组
			$this->group            = '';
			return $this;
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
	 			$this->sql = rtrim($sql, ',').') '.rtrim($val, ',').')';
				$this->exec($this->sql);
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
 			$this->sql = $sql;
 			$this->prepare($this->sql);
 			$this->execute();
 			return Db::$link->lastInsertId();
	 	}

	 	/**
		 * 清空表
		 */	
	 	public function deleteAll(){
	 		$this->sql = "TRUNCATE {$this->tableName}";
 			$this->prepare($this->sql);
 			$this->execute();
	 	}

	 	public function deleteAllAction(){
	 		$this->sql = "DELETE FROM {$this->tableName}";
 			$this->prepare($this->sql);
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

	 	/**
		 * 生成数据的筛选条件
		 * @param  array $where 封装好的where数组
		 * @return thisObject
		 */	
	 	public function setWhere($where){
	 		if(!empty($where)){
		 		// 将$where转化成sql语句
		 		$whereString = '';
		 		foreach ($where as $whereAttr => $whereVal) {
		 			$whereString .= is_string($whereVal) ? " `{$whereAttr}` = '{$whereVal}' AND" : " `{$whereAttr}` = {$whereVal} AND";
		 		}
		 		$whereString = rtrim($whereString, 'AND');
		 		$this->where($whereString);
	 		} 
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
		 * 分组查询
		 * @param  string $group 分组
		 */	
	 	public function group($group){
	 		if(!is_string($group)) return $this;
	 		$this->group = ' GROUP BY '.$group;
	 		return $this;
	 	}

	  /**
		 * 查看sql语句
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
		 * 生成排序
		 * @param $orderbys 生成的Order的数组
		 * @return object
		 */	 	
	  public function setOrder($orderbys){
	 		if(!empty($orderbys)){
	 			// 将$orderby转换成sql语句
	 			$orderString = '';
	 			foreach ($orderbys as $orderbyKey => $orderby) {
	 				$orderString .= " {$orderby['column']} ".($orderby['asc'] == true ?'ASC':'DESC').',';
	 			}
	 			$orderString = rtrim($orderString, ',');
	 			$this->order($orderString);
	 		}
	  }

	  /* 限制 */
	  public function limit($limit){
	  	if ($limit != '')
		  	$this->limit = ' LIMIT '.$limit;
		  else 
		  	$this->limit = '';
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

		 	$sql .= " FROM ".$this->tableName.$this->join.$this->where.$this->group.$this->order.$this->limit;

 			$this->sql = $sql;
	 		$this->prepare($this->sql);
	 		$result = $this->execute();

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
	 	public function update($data = array(), $return = false){
	 		if (count($data) <=0) return;
 			$sql = "UPDATE {$this->tableName} SET ";
 			foreach ($data as $attr => $val) {
 				$sql .= "`{$attr}` = ".(is_string($val)? "'{$val}'" : (int)$val).',';
 			}
	 		$sql = rtrim($sql, ',').$this->where;
	 		if($return) return $sql;
	 		$this->prepare($sql);
	 		$this->execute();
	 		return $this->prepare->rowCount();
	 	}

	 	/**
		 * 批量更新数据
		 * @param array $datas   包含了更新和条件的数组
		 */
	 	public function updates($datas){
	 		// 清空
	 		$this->reset();
	 		// 批量生成数据

	 		if(!empty($datas)){
		 		// 将$where转化成sql语句
		 		$sql = '';
		 		foreach ($datas as $index => $dataVal) {
		 			// 逐条组合成sql
		 			if(!isset($dataVal['where']) || !isset($dataVal['where'])) \BYS\Report::error('数据缺少参数');
			 		$whereString = '';
		 			foreach ($dataVal['where'] as $whereAttr => $whereVal) {
		 				$whereString .= is_string($whereVal) ? " `{$whereAttr}` = '{$whereVal}' AND" : " `{$whereAttr}` = {$whereVal} AND";
		 			}
			 		$whereString = rtrim($whereString, 'AND');
			 		$this->where($whereString);
			 		$sql .= $this->update($dataVal['value'], true).';';
		 		}
		 		$this->sql = $sql;
	 		}else{
	 			\BYS\Report::error('数据为空');
	 		}

	 		$this->prepare($this->sql);
	 		$this->execute();
	 		return $this->prepare->rowCount();
	 	}

	 	/**
		 * 更新多条数据
		 * $where的索引对应着$data二维数组的索引
		 * 多个 update 一个 where
		 * @return mix 结果
		 */	 	
	 	public function updateAll($where = array(), $data = array()){
	 		if (count($data) <=0) return;
	 		$sql = '';
	 		foreach ($where as $key => $whereVal) {
	 			$sql .= "UPDATE {$this->tableName} SET ";
	 			foreach ($data[$key] as $attr => $val) {
	 				$sql .= "`{$attr}` = ".(is_string($val)? "'{$val}'" : (int)$val).',';
	 			}
	 			$sql = rtrim($sql, ',');
	 			$sql .= ' WHERE '.$whereVal.';';
	 		}
	 		$this->sql = $sql;
	 		$this->prepare($this->sql);
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
	        $this->sql = $sql;
	        $this->prepare = Db::$link -> prepare($this->sql); // 返回类似于数组
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
	        if($this->prepare->rowCount() > 1){
	        	return $this->prepare->fetchALL();
	        }
	        else{
		        $result = $this->prepare->fetch();
		        if(is_array($result)){
		        	return array(0=>$result);
		        }
	        	return $result;
	        }
	        
	        	
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
		
	 	/** 
		 封装的SQL事件
		 */

	 	/**
		 * 查询数据
		 * @param array $where   条件数组
		 * @param array $select  字段数组
		 * @param array $orderby 排序数组
		 */
	 	public function find($where = array(), $orderbys = array(), $select = array()){
	 		// 清空
	 		$this->reset();
	 		
	 		// 生成条件
		 	$this->setWhere($where);
	 		
		 	// 生成排序
		 	$this->setOrder($orderbys);
		 	
	 		// 默认限制再1000条数据
	 		return $this->limit(1000)->select(implode($select, ','));
	 	}

	}
?>