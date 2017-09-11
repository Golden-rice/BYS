<?php
namespace admin\Model;
use BYS\Model;
class HotCityModel extends Model {
	protected $tablePrefix = "e_mix_";
	protected $_validate = array(
		'GmtCreate' => 'TIME',
		'GmtModified' => 'TIME',
	);
}