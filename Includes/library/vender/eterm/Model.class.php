<?php
namespace Eterm;
use BYS\Model;
class CommandModel extends Model {
	function __construct(){
		// 设置前缀
		$this->tablePrefix = 'e_cmd_';
	}

	/**
   * 创建表单，供两个表 source 及 detail
   * @param  staring $tableName 表单名
   * @return bool    
	 */
	public function build($tableName = '', $SourceConstruct = array()){

		$name = $this->tablePrefix.$tableName;

		try {
			if(!$this->haveTable($name.'_source'))
				parent::_creat($name.'_source', $this->SourceTable());
			if(!$this->haveTable($name.'_result'))
				parent::_creat($name.'_result', $this->ResultTable($SourceConstruct));
		}catch(Exception $e){
			BYS\Report::error($e);
			return false;
		}

		return true;
	}

	/**
   * 判断是否有该表单
   * @param  staring $tableName 表单名
   * @return boolen             是否含有
	 */
	private function haveTable($tableName){

		return false;
	}

	/**
   * 返回资源表的表单结构，所有命令填充至一条中
   * @return array             
	 */
	private function SourceTable(){
		return array(
			'Id'         => 'bigint(20) NOT NULL AUTO_INCREMENT',
			// 关键字
			'FareKey'    => 'varchar(100) NOT NULL', 
			// 命令
			'Command'    => 'varchar(100) NOT NULL',
			// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
			'Status'     => 'int(1) NOT NULL',
			// OFFICE 号
			'Office'     => 'char(10) DEFAULT NULL',
			// 结果
			'Detail'     => 'longtext DEFAULT NULL',
			// 创建时间
			'GmtCreate'  => 'datetime NOT NULL',
			// 修改时间
			'GmtModified'=> 'datetime DEFAULT NULL',

		);
	}

	/**
   * 返回解析表的表单结构
   * @param  $SourceConstruct  资源表格式
   * @return array             
	 */
	private function ResultTable($SourceConstruct = array()){
		if(isset($SourceConstruct)){
			return $SourceConstruct;
		}else{
			return array();
		}
	}
}