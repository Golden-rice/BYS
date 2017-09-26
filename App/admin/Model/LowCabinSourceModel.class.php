<?php
namespace admin\Model;
use BYS\Model;
class LowCabinSourceModel extends Model {
	protected $tablePrefix = "e_cmd_mix_";
	protected $_validate = array(
		'GmtCreate' => 'TIME',
		'GmtModified' => 'TIME',
	);
}