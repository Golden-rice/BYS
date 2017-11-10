<?php
namespace admin\Model;
use BYS\Model;
class SkResultModel extends Model {
	protected $tablePrefix = "e_cmd_";
	protected $_validate = array(
		'GmtCreate' => 'TIME',
		'GmtModified' => 'TIME',
	);
}