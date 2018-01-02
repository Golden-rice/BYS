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
		// 最后插入的ID
		protected $lastInsertId     = null;
		// 最后受影响的属相
		protected $rowCount         = null;
		// 绑定参数
		private   $value            = array();

	 	function __construct($className = ""){
		 	// 声明全局变量
		 	BYS::$_GLOBAL['mod_path'] = BYS::$sitemap['Model'];
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
		 * 生成待处理的数据
		 * @param  array $data 处理的函数
		 */
	 	public function ready($data){
	 		$this->data = $data;
	 		return $this;
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
			// 最后插入的ID
			$this->lastInsertId     = null;
			// 最后受影响的属相
			$this->rowCount         = null;
			// 绑定参数
			$this->value            = array();
			return $this;
	 	}


	 	/**
		 * 新增数据，返回最后添加的id
		 * @param  array  $data         新增的数据
		 * @param  bool   $return       是否返回sql且停止执行
		 * @param  bool   $setAttrName  是否已key值作为占位符
		 * @return int    
		 */	 	
	 	public function add($data=array(), $return = false, $setAttrName = false){
	 		// 单条新增
	 			$sql = '';

	 			$sql = "INSERT INTO {$this->tableName} (";
			 	$val = 'VALUES(';
	 			foreach ($data as $key => $value) {
	 				$sql .= "`{$key}`,";

	 				// sql 语句
	 				// $val .= is_string($value)? "'$value'," : $value.',';

	 				// PDO 设置相同数量的占位符
	 				// if($setAttrName){
			 		// 	$val .= ":{$value},"; // :{$value}
			 		// 	$this->addBindValue(array(":{$value}", $value, is_numeric($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR)); // :{$value}
	 				// }else{
			 		// 	$val .= "?,"; // :{$value}
			 		// 	$this->addBindValue(array("?", $value, is_numeric($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR)); // :{$value}
	 				// }

		 			$val .= $setAttrName ? $this->setPlacehold(":{$value}", "%,", $value) : $this->setPlacehold("?", "%,", $value);

	 			}
	 			if(count($this->_validate) >0){
	 				foreach ($this->_validate as $key => $value) {
	 					$sql .= "`{$key}`,";
	 					// sql 语句
	 					// if($value == 'TIME'){
	 					// 	$val .= time().',';
	 					// }else{
		 				// 	$val .= is_string($value)? "'$value'," : $value.',';
	 					// }

						// PDO 设置相同数量的占位符
						// if($setAttrName){
				 	// 		$val .= ":{$value},"; 
					 // 		$this->addBindValue(array(":{$value}", $value === 'TIME' ? time() : $value, $value === 'TIME' || is_numeric($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR)); // :{$value}
						// }else{
					 // 		$val .= "?,"; // :{$value}
					 // 		$this->addBindValue(array("?", $value === 'TIME' ? time() : $value, $value === 'TIME' || is_numeric($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR)); // :{$value}
						// }
						$val .= $setAttrName ? $this->setPlacehold(":{$value}", "%,", $value === 'TIME' ? time() : $value) : $this->setPlacehold("?", "%,", $value === 'TIME' ? time() : $value);

	 				}
	 			}
	 			$sql = rtrim($sql, ',').') '.rtrim($val, ',').');';
	 			$this->sql = $sql;
				if($return) return $sql;

				$this->prepare($this->sql);
				$this->execute();

				return $this->lastInsertId;
	 	}


	 	/**
		 * 新增多条数据
		 * @param  array  $data 新增的数据
		 * @return bool 
		 */	 
	 	public function addAll($data=array()){

	 		if (count($data) <=0) return;

 			// sql 语句
 			// $sql = '';
 			// foreach ($data as $index => $item) {
 			// 	$sql .= $this->add($item, true);	
 			// }
	 		try{
	 			Db::$link->beginTransaction(); 

	 			// PDO 设置一次占位符
		 		foreach ($data as $index => $item) {
		 			$this->sql = $this->add($item, true);
		 			$this->prepare($this->sql);
		 			$this->setBindValue();
		 			$this->prepare->execute();
		 		}
		 		$this->lastInsertId = Db::$link->lastInsertId();
		 		Db::$link->commit();
	 			return $this->lastInsertId;
 			}catch(PDOException $e){
	    	Db::$link->rollback();
	      Report::error('错误是：'.$e->getMessage().' From Model::addAll');
	    }
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
		 * @param  array $where        封装好的where数组
		 * @param  bool  $setAttrName  是否已key值作为占位符
		 * @return thisObject
		 */	
	 	public function setWhere($where, $setAttrName = false){
	 		if(!empty($where)){
		 		$whereString = '';

		 		foreach ($where as $whereAttr => $whereVal) {
		 			// 过滤值为空的条件，值为0，''时
		 			// if(empty($whereVal)) continue;
		 			// 设置一个条件多个参数时
		 			if(is_array($whereVal)) {
				 		// 将$where转化成sql语句
		 				// 判断值是否为数字
		 				// if(is_numeric($whereVal[0]))
			 			// 	$whereString .= " `{$whereAttr}` IN (".implode(",", $whereVal).") AND";
			 			// else
			 			// 	$whereString .= " `{$whereAttr}` IN ('".implode("','", $whereVal)."') AND";

				 		// 利用PDO绑定防止sql 注入
			 			// 设置相同数量的占位符
			 			// $whereString .= " `{$whereAttr}` IN (".implode(",", array_fill(0, count($whereVal), '?')).") AND";
			 			// $this->addBindValue(array("?", $whereVal, is_numeric($whereVal) ? \PDO::PARAM_INT : \PDO::PARAM_STR));

			 			$whereString .= $this->setPlacehold("?", " `{$whereAttr}` IN (".implode(",", array_fill(0, count($whereVal), '%')).") AND", $whereVal);

		 			}
	 				// 设置一般条件
		 			else{
		 				// 将$where转化成sql语句
		 				// $whereString .= is_string($whereVal) ? " `{$whereAttr}` = '{$whereVal}' AND" : " `{$whereAttr}` = {$whereVal} AND";

		 				// 利用PDO绑定防止sql 注入
		 				// 参数标识符 已取消
		 				// if($setAttrName){
			 			// 	$whereString .= " `{$whereAttr}` = :{$whereAttr} AND"; 
			 			// 	$this->addBindValue(array(":{$whereAttr}", $whereVal, is_numeric($whereVal) ? \PDO::PARAM_INT : \PDO::PARAM_STR));
		 				// }else{
			 			// 	$whereString .= " `{$whereAttr}` = ? AND"; // :{$whereAttr}
			 			// 	$this->addBindValue(array("?", $whereVal, is_numeric($whereVal) ? \PDO::PARAM_INT : \PDO::PARAM_STR)); // :{$whereAttr}
		 				// }
		 				if(preg_match("/(<|>)(.*)?/", $whereVal, $whereValMatch)){
		 					$whereVal     = isset($whereValMatch[2]) ? $whereValMatch[2] : $whereVal;
		 					$whereString .= $setAttrName ? $this->setPlacehold(":{$whereAttr}", "`{$whereAttr}` {$whereValMatch[1]} % AND", $whereVal) : $this->setPlacehold("?", "`{$whereAttr}` {$whereValMatch[1]} % AND", $whereVal);
		 				}
		 				else
		 					$whereString .= $setAttrName ? $this->setPlacehold(":{$whereAttr}", "`{$whereAttr}` = % AND", $whereVal) : $this->setPlacehold("?", "`{$whereAttr}` = % AND", $whereVal);
		 			}
		 		}
		 		$whereString = rtrim($whereString, 'AND');
		 		$this->where($whereString);

	 		} 
	 		return $this;
	 	}

	 	/**
	 	 * 设置绑定
	 	 * @param $param  待绑定的占位符，格式 绑定字段，值，设置1，设置2
	 	 */
	 	public function addBindValue($param = array()){
	 		// key 为绑定次序
	 		if(!empty($param)) array_push($this->value, $param);
	 	}


	 	public function join($type, $sql){
	 		if(!is_string($type) || !is_string($sql)) return $this;

	 		switch (strtoupper($type)) {
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
	  public function distinct($distinct){
	  	if ($distinct != '')
		  	$this->distinct = ' DISTINCT '.$distinct;
		  else 
		  	$this->distinct = '';
	  	return $this;
	  }

	  /**
		 * 设置去重
		 */	
	  public function setDistinct($distincts){
	 		if(!empty($distincts)){
	 			// 将$orderby转换成sql语句
	 			$distinctString = '';
	 			foreach ($distincts as $distinct) {
	 				$distinctString .= " {$distinct} ,";
	 			}
	 			$distinctString = rtrim($distinctString, ',');
	 			$this->distinct($distinctString);
	 		}
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
	 			// $orderbys 的key 是变量名， value 决定是生还是降
	 			$orderString = '';
	 			foreach ($orderbys as $orderName => $orderType) {
	 				$orderString .= " {$orderName} ".($orderType === 'DESC' ? 'DESC' : 'ASC').',';
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

	  public function setLimit($limit){
	  	if(!empty($limit))
	  		$this->limit($limit);
	  	else
	  		$this->limit(1000);
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
	 		$result = $this->execute(true);

	 		return $result;
	 	}

	 	/**
		 * 更新一条数据
		 * @param  array  $data         待更新的字段及数据
		 * @param  array  $where        更新条件
		 * @param  bool   $return       是否sql语句且停止执行
		 * @param  bool   $setAttrName  是否已key值作为占位符
		 * @return string               SQL语句
		 */	
	 	public function setUpdate($data = array(), $setAttrName = false){
	 		if (count($data) <=0) return;
	 		$sql = '';
 			foreach ($data as $attr => $val) {
 				// SQL 书写
 				// $sql .= "`{$attr}` = ".(is_string($val)? "'{$val}'" : (int)$val).',';

 				// PDO 绑定
 				$sql .= $setAttrName ? $this->setPlacehold(":{$attr}", " `{$attr}` = % ,", $val) : $this->setPlacehold("?", " `{$attr}` = % ,", $val);

 			}
	 		return rtrim($sql, ',');
	 	}

	 	/**
		 * 更新一条数据
		 * 为兼容原来的代码 setAttrName 都设置成true
		 * @param  array $data         待更新的字段及数据
		 * @param  array $where        更新条件
		 * @param  bool  $return       是否sql语句且停止执行
		 * @param  bool  $setAttrName  是否已key值作为占位符
		 * @return mix                 数据库查询结果
		 */	 	
	 	public function update($data = array(),  $where = array(), $return = false, $setAttrName = true){
	 		if (count($data) <=0) return;

	 		// 清空
	 		$this->reset();

 			$sql = "UPDATE {$this->tableName} SET ";
 			// foreach ($data as $attr => $val) {
 				// SQL 书写
 				// $sql .= "`{$attr}` = ".(is_string($val)? "'{$val}'" : (int)$val).',';

 				// PDO 绑定
 			// 	$sql .= $setAttrName ? $this->setPlacehold(":{$attr}", " `{$attr}` = % ,", $val) : $this->setPlacehold("?", " `{$attr}` = % ,", $val);

 			// }
	 		$this->setWhere($where, $setAttrName);
	 		// $sql = rtrim($sql, ',').$this->where;
	 		$sql = $sql.$this->setUpdate($data, $setAttrName).$this->where;
	 		$this->sql = $sql;

	 		if($return) return $this->sql;

	 		$this->prepare($this->sql);
	 		$this->execute();
	 		return $this->rowCount;
	 	}



	 	/**
		 * 批量更新数据
		 * @param array $datas   包含了更新和条件的数组
		 */
	 	public function updates($datas){
	 		// 清空
	 		$this->reset();
	 		if(empty($datas)) \BYS\Report::error('数据为空'.' From Model::updates');
	 		
	 		$sql = '';
	 		try{
	 			Db::$link->beginTransaction(); 
	 			// PDO 设置一次占位符
		 		foreach ($datas as $index => $dataVal) {
		 			if(!isset($dataVal['where']) || !isset($dataVal['where'])) \BYS\Report::error('数据缺少参数'.' From Model::updates');
		 			$this->sql = $this->update($dataVal['value'], $dataVal['where'], true, true);

		 			$this->prepare($this->sql);
		 			$this->setBindValue();
		 			$this->prepare->execute();

		 		}
		 		$this->rowCount  += $this->prepare->rowCount();
		 		Db::$link->commit();

		 		return $this->rowCount;

		 	}catch(PDOException $e){
	    	Db::$link->rollback();
	      Report::error('错误是：'.$e->getMessage().' From Model::updates');
	    }
	 	}


	 	/**
		 * 删除数据
		 * @return mix 结果
		 */	 	
	 	public function delete(){
 			if(empty($this->where)) {
 				Report::log('缺少删除条件！'.' From Model::delete');
 				return;
 			}
 			$sql = "DELETE FROM {$this->tableName} ";
	 		$sql = $sql.$this->where;

	 		$this->prepare($sql);
	 		$this->execute();
	 		return $this->rowCount;
	 	}


	 	/**
		 * 事务预处理：SELECT 
		 * @param  string $sql 查询语句
		 */
	 	public function prepare($sql = ''){
	 		if($sql == '') Report::warning('无查询语句');
		 	try{
	      $this->sql = $sql;
	      $this->prepare = Db::$link->prepare($this->sql); // 返回类似于数组
	    }catch(PDOException $e){
	      Report::error('错误是：'.$e->getMessage().' From Model::prepare');
	    }
	 	}

	 	/**
		 * 占位符绑定模式：默认用? 占位，开启后用字段占位
		 * @param  string  $placeHold  替换占位符%的字符串
		 * @param  string  $place      替换模板
		 * @param  string  $val        待绑定参数的值
		 */
	 	public function setPlacehold($placeHold = '?', $place, $val){
	 		// 除了？相同占位符名时重命名
	 		if($placeHold !== '?'){
		 		foreach ($this->value as $value) {
		 			if($value[0] === $placeHold){
		 				$placeHold = $placeHold.time();
		 			}
		 		}
	 		}
	 		$this->addBindValue(array($placeHold, $val, is_numeric($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR));
	 		return preg_replace("/%/", $placeHold, $place);
	 	}

	 	/**
		 * 绑定参数
		 */
	 	public function setBindValue(){
			if(!empty($this->value)){
				$totelHoldPlace = 0; // ?占位符位置
				foreach ($this->value as $paramVal) {

					if($paramVal[0] === '?'){
						if(is_array($paramVal[1]))
							foreach ($paramVal[1] as $paramValEle) 
								$this->prepare->bindParam(++$totelHoldPlace, $paramValEle, $paramVal[2]);
						else{
							$this->prepare->bindParam(++$totelHoldPlace, $paramVal[1], $paramVal[2]);
						}
					}
					else{
						// 参数标识符
						$this->prepare->bindParam($paramVal[0], $paramVal[1], $paramVal[2]);
					}
				}
				// 清空
				$this->value = array();
			}
	 	}

	 	/**
		 * 事务预处理 执行
		 * @param  array $returnArray 返回结果是不是数组
		 * @return array
		 */
	 	public function execute($returnArray = false){
	 		if( !isset($this->prepare)) Report::error('缺少预处理, From Model::execute {$_SERVER["SCRIPT_NAME"]}');
		 	try{
				Db::$link->beginTransaction(); 
				// 新增 Debug: 打印数据库执行
				var_dump($this->sql, $this->value);
				// ***
				// 绑定值
				$this->setBindValue();
        $result             = $this->prepare->execute();
        $this->rowCount     = $this->prepare->rowCount();
        $this->lastInsertId = Db::$link->lastInsertId();

	      // SELECT 语句返回结果是数组
        if($returnArray){
		      if($this->rowCount > 1){
		      	$result = $this->prepare->fetchALL();
		      }
		      else{
		        $result = $this->prepare->fetch();
		        if(is_array($result)){
		        	$result = array(0=>$result);
		        }
		      }
        }

        Db::$link->commit();
        return $result;
	    }catch(PDOException $e){
	    	Db::$link->rollback();
	      Report::error('错误是：'.$e->getMessage().' From Model::execute');
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
	 	public function find($where = array(), $orderbys = array(), $select = array(), $distinct = array(), $limit = 1000){
	 		// 清空
	 		$this->reset();
	 		
	 		// 生成条件
		 	$this->setWhere($where);
	 		
		 	// 生成排序
		 	$this->setOrder($orderbys);
		 	
		 	// 生成区分
		 	$this->setDistinct($distinct);

		 	// 生成限制条件
		 	$this->setLimit($limit);

	 		return $this->select(implode($select, ','));
	 	}

	 	/** 
		 废弃函数，仅为遗留的方法保留
		 */

	 	/**
		 * 更新多条数据
		 * $where的索引对应着$data二维数组的索引
		 * 多个 update 一个 where
		 * @return mix 结果
		 */	 	
	 	public function updateAll($where = array(), $data = array()){
	 		if (count($data) <=0) return;
	 		$sql = '';
	 		$updates = array();
	 		foreach ($data as $key => $value) {
	 			array_push($updates, array('where'=>$where, 'value'=>$value));
	 		}
	 		return $this->updates($updates);

	 		// 废弃代码
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
	 		return $this->rowCount;
	 	}

	 	// public function add($data=array()){
	 	// 	// 单条新增
	 	// 		$sql = '';

	 	// 		$sql = "INSERT INTO {$this->tableName} (";
			//  	$val = 'VALUES(';
	 	// 		foreach ($data as $key => $value) {
	 	// 			$sql .= "`{$key}`,";
	 	// 			$val .= is_string($value)? "'$value'," : $value.',';
	 	// 		}
	 	// 		if(count($this->_validate) >0){
	 	// 			foreach ($this->_validate as $key => $value) {
	 	// 				$sql .= "`{$key}`,";
	 	// 				if($value == 'TIME'){
	 	// 					$val .= time().',';
	 	// 				}else{
		 // 					$val .= is_string($value)? "'$value'," : $value.',';
	 	// 				}
	 	// 			}
	 	// 		}
	 	// 		$this->sql = rtrim($sql, ',').') '.rtrim($val, ',').')';
			// 	$this->prepare($this->sql);
			// 	$this->execute();

			// 	return $this->lastInsertId;
	 	// }
	}
?>