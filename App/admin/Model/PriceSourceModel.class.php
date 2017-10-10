<?php
namespace admin\Model;
use BYS\Model;
class PriceSourceModel extends Model {
	protected $tablePrefix = "e_cmd_";
	protected $_validate = array(
		'GmtCreate' => 'TIME',
		'GmtModified' => 'TIME',
	);
}