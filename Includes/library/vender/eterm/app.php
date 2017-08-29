<?php

// eterm 核心库
include 'Eterm.class.php';
// 表单类
include 'Model.class.php';

define('ETERM_ROOT', VEND_PATH.'eterm/');

// 获取 eterm 命令库
autoload( autoReadCommand(ETERM_ROOT, 'Eterm') );

/** 
 * 自动读取command类库
 * @param  $path       路径
 * @param  $namespace  命名空间
 * @return void
 */
function autoReadCommand($path, $namespace = ""){
	$pathMap = array();

	if( is_dir($path) && $handle = opendir($path) ){
		while( ($file = readdir($handle)) !== false ){
			if( $file!='.' && $file!='..' ){
				preg_match("/^(\w+)\.command\.php/", $file, $name);
				if( isset($name[1]) )  array_push($pathMap, "{$name[1]}.command.php");
			}
		}
	}else{
		// 报错: 没有该类库
		BYS\Report::error("没有该类库");
	}
	return $pathMap;
}

/** 
 * 自动加载map中的类库，并返回实例后的类组成的数组
 * @param  array $map 待加载类名
 */
function autoload($map){
	foreach ($map as $file) {
		if( is_file(ETERM_ROOT.$file) ) {
        include ETERM_ROOT.$file;
    }
  }
}

// 创建命令的表单 source 及 detail 
$a = new Eterm\CommandModel();

// xfsd source 及 result
$a->build('xfsd', array(
	'Id'         => 'bigint(20) NOT NULL AUTO_INCREMENT',
	// 关键字，格式：dep_city/arr_city/airline/pax_type/source/source_office/source_agreement(大客户编号)/other/fare_date 例如："BJS/MIA/UA/ADT/1E/BJS248///20170825"
	'FareKey'    => 'varchar(100) NOT NULL', 
	// 命令
	'Command'    => 'varchar(100) NOT NULL',
	// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
	'Status'     => 'int(1) NOT NULL',
	// OFFICE 号
	'Office'     => 'char(10) DEFAULT NULL',
	// source id
	'Sid'        => 'bigint(20) NOT NULL',
	// fare FareBasis
	'FareBasis'      => 'varchar(10) NOT NULL',
	// special 特殊规则
	'xfsd_Special'   => 'varchar(3) DEFAULT NULL',
	// advp 提前出票
	'xfsd_Advp'      => 'varchar(10) DEFAULT NULL',
	// allowDateStart 适用日期起始
	'xfsd_DateStart' => 'datetime DEFAULT NULL',
	// allowDateEnd 适用日期结束
	'xfsd_DateEnd'   => 'datetime DEFAULT NULL',
	// backLineFee 往返费用
	'xfsd_RoundFee'  => 'int(10) NOT NULL',
	// singleLineFee 单程费用
	'xfsd_SingleFee' => 'int(10) NOT NULL',
	// start 出发
	'xfsd_Dep'       => 'char(3) NOT NULL',
	// end 到达
	'xfsd_Arr'       => 'char(3) NOT NULL',
	// aircompany 航空公司
	'xfsd_Owner'     => 'char(3) NOT NULL',
	// direction 区域
  'xfsd_Region'    => 'char(3) NOT NULL',
	// allowWeek 作用点
  'xfsd_Indicator' => 'char(7) DEFAULT NULL',
	// maxStay 最大停留
  'xfsd_MaxStay'   => 'char(3) DEFAULT NULL',
	// minStay 最短停留
 	'xfsd_MinStay'   => 'char(3) DEFAULT NULL',
	// seat 舱位
 	'xfsd_Cabin'     => 'char(3) NOT NULL',
	// fromCode
 	'xfsd_Code'      => 'varchar(20) DEFAULT NULL',
	// 创建时间
	'gmtCreate'      => 'int(13) NOT NULL',
	// 修改时间
	'gmtModified'    => 'int(13) DEFAULT NULL',
));

// avh souce 及 result
$a->build('avh',array(
	'Id'            => 'bigint(20) NOT NULL AUTO_INCREMENT',
	// 命令
	'Command'       => 'varchar(100) NOT NULL',
	// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
	'Status'        => 'int(1) NOT NULL',
	// OFFICE 号
	'Office'        => 'char(10) DEFAULT NULL',
	// source id
	'Sid'           => 'bigint(20) NOT NULL',
	// start 出发
	'avh_Dep'       => 'char(3) NOT NULL',
	// end 到达
	'avh_Arr'       => 'char(3) NOT NULL',
	// startTime 出发时间
	'avh_DepTime'   => 'time NOT NULL',
	// endTime 到达时间
	'avh_ArrTime'   => 'time NOT NULL',
	// startDate 查询日期
	'avh_Date'      => 'datetime DEFAULT NULL',
	// flight 航班号
	'avh_Flight'    => 'varchar(8) NOT NULL',
	// carrier 实际承运
	'avh_Operation' => 'varchar(8) DEFAULT NULL',
	// cabin 舱位
	'avh_Cabin'     => 'varchar(1000) NOT NULL',
	// airType 机型
	'avh_AirType'   => 'varchar(10) DEFAULT NULL',
	// 是否直达
  'avh_IsDirect'  => 'int(1) NOT NULL',
  // 航段组合id sid - id
  'avh_Rid'       => 'varchar(10) NOT NULL',
	// 创建时间
	'gmtCreate'     => 'int(13) NOT NULL',
	// 修改时间
	'gmtModified'   => 'int(13) DEFAULT NULL',
));

// fsl source
$a->build('fsl');