<?php
namespace Eterm;
use BYS\Model;
class CommandModel extends Model {
	function __construct(){
		// 设置前缀
		$this->tablePrefix = 'e_cmd_';
	}

	/**
   * 创建表单，供两个表
	 */
	public function build(){
		parent::_creat('aa', $this->SourceTable());
	}

	/**
   * 判断是否有该表单
   * @param  staring $tableName 表单名
   * @return boolen             是否含有
	 */
	private function haveTable($tableName){

		return true;
	}

	private function SourceTable(){
		return array();
	}

	private function ResultTable(){
		return array();
	}
}