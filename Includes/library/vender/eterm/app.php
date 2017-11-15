<?php

// eterm 核心库
include_once 'Eterm.class.php';
// 表单类
include_once 'Model.class.php';

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
        include_once ETERM_ROOT.$file;
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
	'xfsd_DateStart' => 'date DEFAULT NULL',
	// allowDateEnd 适用日期结束
	'xfsd_DateEnd'   => 'date DEFAULT "2099-12-30"',
	// backLineFee 往返费用
	'xfsd_RoundFee'  => 'float(10,2) NOT NULL',
	// singleLineFee 单程费用
	'xfsd_SingleFee' => 'float(10,2) NOT NULL',
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
 	// reticket 退改规定
 	'xfsd_Rule'      => 'varchar(5) DEFAULT NULL',
	// 创建时间
	'gmtCreate'      => 'int(13) NOT NULL',
	// 修改时间
	'gmtModified'    => 'int(13) DEFAULT NULL',
	// 使用规则字段：销售日期
	'SaleDate'       => 'varchar(200) DEFAULT NULL'
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
	'avh_DepTime'   => 'datetime NOT NULL',
	// endTime 到达时间
	'avh_ArrTime'   => 'datetime NOT NULL',
	// flightTime 飞行日期
	'avh_FlightTime'   => 'time NOT NULL',
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

// fsl souce 及 result
$a->build('fsl', array(
	'Id'            => 'bigint(20) NOT NULL AUTO_INCREMENT',
	// 命令
	'Command'       => 'varchar(100) NOT NULL',
	// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
	'Status'        => 'int(1) NOT NULL',
	// OFFICE 号
	'Office'        => 'char(10) DEFAULT NULL',
	// source id
	'Sid'           => 'bigint(20) NOT NULL',
	// routing 原数据
	'Fsl_Source'    => 'varchar(500) NOT NULL', 
	// 筛选从中国出发到美国的中转城市
	'Fsl_Translate' => 'varchar(100) DEFAULT NULL',
	// 合并后的中转城市
	'Fsl_result'    => 'varchar(500) DEFAULT NULL',
	// 创建时间
	'gmtCreate'     => 'int(13) NOT NULL',
	// 修改时间
	'gmtModified'   => 'int(13) DEFAULT NULL',
));

// sk souce 及 result
$a->build('sk', array(
	'Id'            => 'bigint(20) NOT NULL AUTO_INCREMENT',
	// 命令
	'Command'       => 'varchar(100) NOT NULL',
	// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
	'Status'        => 'int(1) NOT NULL',
	// OFFICE 号
	'Office'        => 'char(10) DEFAULT NULL',
	// source id
	'Sid'           => 'bigint(20) NOT NULL',
	// 航班号
	'Sk_Flight'     => 'varchar(5) NOT NULL', 
	// 出发
	'Sk_Dep'        => 'char(3) NOT NULL',
	// 到达
	'Sk_Arr'        => 'char(3) NOT NULL',
	// 到达
	'Sk_Aircompany' => 'char(2) DEFAULT NULL',
	// 出发时间
	'Sk_DepTime'    => 'char(3) DEFAULT NULL',
	// 到达时间
	'Sk_ArrTime'    => 'char(3) DEFAULT NULL',
	// 是否直达
  'Sk_IsDirect'   => 'int(1) NOT NULL',
  // 分组 Id-航路序号-航段序号
  'Sk_Rid'        => 'varchar(10) NOT NULL',
  // 作用点，将X转换成可用
  'Sk_AllowWeek'  => 'varchar(7) NOT NULL',
  // 起始适用日期
  'Sk_DateStart'  => 'date NOT NULL',
  // 结束适用日期
  'Sk_DateEnd'    => 'date NOT NULL',
  // 未知
  'Sk_Other'      => 'varchar(100) NOT NULL',
	// 创建时间
	'gmtCreate'     => 'int(13) NOT NULL',
	// 修改时间
	'gmtModified'   => 'int(13) DEFAULT NULL',
));

// sk souce 及 result
$a->build('yy', array(
	'Id'            => 'bigint(20) NOT NULL AUTO_INCREMENT',
	// 命令
	'Command'       => 'varchar(100) NOT NULL',
	// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
	'Status'        => 'int(1) NOT NULL',
	// OFFICE 号
	'Office'        => 'char(10) DEFAULT NULL',
	// source id
	'Sid'           => 'bigint(20) NOT NULL',
	// 出发区域
	'Yy_Start_Input'=> 'varchar(3) NOT NULL',
	// 到达区域
	'Yy_End_Input'  => 'varchar(3) DEFAULT NULL',
	// 出发
	'Yy_Start'      => 'char(3) NOT NULL',
	// 到达
	'Yy_End'        => 'char(3) NOT NULL',
	// 航空公司
	'Yy_Aircompany' => 'char(2) NOT NULL',
	// 是否是共享
	'Yy_IsCommon'   => 'int(1) NOT NULL',
	// 创建时间
	'gmtCreate'     => 'int(13) NOT NULL',
	// 修改时间
	'gmtModified'   => 'int(13) DEFAULT NULL',
));