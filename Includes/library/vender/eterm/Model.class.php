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

			// price_source 同步燕峰提供的使用规则数据
			if(!$this->haveTable($this->tablePrefix.'price_source'))
				parent::_creat($this->tablePrefix.'price_source', $this->PriceSource());
			if(!$this->haveTable($this->tablePrefix.'price_result'))
				parent::_creat($this->tablePrefix.'price_result', $this->PriceResult());
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
			// 关键字，格式：dep_city/arr_city/airline/pax_type/source/source_office/source_agreement//fare_date 例如："BJS/MIA/UA/ADT/1E/BJS248///20170825"
			'FareKey'    => 'varchar(100) NOT NULL', 
			// 命令
			'Command'    => 'varchar(100) NOT NULL',
			// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
			'Status'     => 'int(2) NOT NULL',
			// OFFICE 号
			'Office'     => 'char(10) DEFAULT NULL',
			// 结果
			'Detail'     => 'longtext DEFAULT NULL',
			// 创建时间
			'GmtCreate'  => 'int(13) NOT NULL',
			// 修改时间
			'GmtModified'=> 'int(13) DEFAULT NULL',

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

	/**
   * 返回燕峰的政策表结构
   * @return array             
	 */
	private function PriceSource(){
		return array(
			// String id;
			'Id'         => 'bigint(20) NOT NULL AUTO_INCREMENT',
			// String fareKey;
			'FareKey'    => 'varchar(100) NOT NULL',
			// Integer fareDate; 查询日期
			'FareDate'   => 'int(8) DEFAULT NULL',
			// String fareCabin; 查询舱位
			'FareCabin'  => 'varchar(100) DEFAULT NULL',
			// String fareGroupToken; 运价分组
			// String route; 航路
			'Route'      => 'varchar(300) DEFAULT NULL',
			// Integer nego; 运价类型：0: "公布运价", 1: "私有运价"
			'Nego'       => 'tinyint(1) DEFAULT NULL',
			// String dep; 出发
			'Dep'        => 'char(3) DEFAULT NULL',
			// String arr; 到达
			'Arr'        => 'char(3) NOT NULL',
			// Integer depType; 出发类型
			// Integer arrType; 到达类型
			// String airline; 航司
			'Airline'    => 'char(3) NOT NULL',
			// String farebasis; fare 
			'FareBasis'  => 'varchar(10) NOT NULL',
			// String cabin; 舱位
			'Cabin'      => 'char(1) NOT NULL',
			// String ticketingCode; 开票代码
			// String soldRegion; 销售地
			'SoldeRegion'=> 'varchar(100) DEFAULT NULL',
			// String passengerType; 乘客类型
			'PassengerType'=>'char(3) DEFAULT NULL',
			// BigDecimal chdFareScale; 儿童折扣
			'ChdFareScale' =>'varchar(3) DEFAULT NULL',
			// String personNum; 最少旅客人数
			'PersonNum'  => 'varchar(5) DEFAULT NULL',
			// String stopLimit; 停留限制
			'StopLimit'  => 'varchar(7) DEFAULT NULL',
			// String sellDate; 销售日期
			'SellDate'   => 'varchar(200) DEFAULT NULL',
			// String outboundDateExcept; 去程除外日期
			'OutboundDateExcept' => 'varchar(200) DEFAULT NULL',
			// String inboundDateExcept; 回程除外日期
			'InboundDateExcept'  => 'varchar(200) DEFAULT NULL',
			// String outboundWeek; 去程周末限制
			'OutboundWeek'=> 'varchar(7) DEFAULT NULL',
			// String inboundWeek; 回程周末限制
			'InboundWeek' => 'varchar(7) DEFAULT NULL',
			// Integer outboundWeekFlag; 回程是否有限制
			// Integer inboundWeekFlag; 去程是否有限制
			// Integer weekLimitType; 是否单独限制去程
			// String flightOnly; 航班限制
			'FlightOnly'  => 'varchar(300) DEFAULT NULL',
			// String flightExcept; 不适用班期
			'FlightExcept'=> 'varchar(300) DEFAULT NULL',
			// String globleFlightOnly; 全局班期限制
			'GlobleFlightOnly'=> 'varchar(300) DEFAULT NULL',
			// String allowHalfRt; 是否允许混舱
			'AllowHalfRt' => 'tinyint(1) DEFAULT NULL',
			// Integer gapState; 缺口规则状态 0不允许，1单缺，2双缺，默认为0
			// String gapRule; 缺口规则
			// Integer allowOpen; 是否为OPEN票 
			'AllowOpen'   => 'tinyint(1) DEFAULT NULL',
			// String ticketBefore; 提前开票
			'TicketBefore'=> 'varchar(10) DEFAULT NULL',
			// String firstWeekRule; 回程是否需要跨星期天
			// String ruleContent; 退改签
			'RuleContent' => 'varchar(500) DEFAULT NULL',
			// BigDecimal singleFare; 单程价格
			'SingleFare' =>'int(10) DEFAULT NULL',
			// BigDecimal roundFare; 往返价格
			'RoundFare'  =>'int(10) DEFAULT NULL',
			// String currency; 币种
			'Currency'   => 'char(3) DEFAULT NULL',
			// BigDecimal roundValue; 货币进制
			// BigDecimal nucRate; NUC 汇率
			// String countryOnly; 允许国家代码
			// String countryExcept; 不允许国家代码
			// Integer countryLimit; 0,1,2;0不启用,1所有乘客满足onlys和excepts,2任一乘客满足onlys和excepts
			// String source; 来源  CTRIP（携程） 1E(航信)
			'Source'      => 'varchar(10) DEFAULT NULL',
			// String tariff; *
			// String airlineRule; 航线
			'AirlineRule' => 'varchar(200) DEFAULT NULL',
			// String outboundSeasonalityDate; 去程可用日期
			'OutboundSeasonalityDate' => 'varchar(200) DEFAULT NULL',
			// String inboundSeasonalityDate; 回程可用日期
			// String outboundTravelDate; 去程旅行日期
			'OutboundTravelDate' => 'varchar(200) DEFAULT NULL',
			// String inboundTravelDate;回程旅行日期
			'InboundTravelDate'  => 'varchar(200) DEFAULT NULL',
			// String fareType; 票价类型
			'FareType'    => 'varchar(5) DEFAULT NULL',
			// Integer travelFlag; 旅行标示 
			// Integer transfers; 转机次数
			'Transfers'   => 'tinyint(2) DEFAULT NULL',
			// Integer cache; 数据是否写入缓存
			// Integer status; 状态 -1 数据错误，0 失效， 1 数据正在失效, 2 生效
			'Status'      => 'tinyint(2) DEFAULT NULL',
			// Date gmtExpire; 失效时间
			// String remark; 备注
			// String surcharges; Q值
			'Surcharges' => 'varchar(200) DEFAULT NULL',
			// String cabinInfo; 储存转机信息
			// 创建时间
			'GmtCreate'  => 'int(13) NOT NULL',
			// 修改时间
			'GmtModified'=> 'int(13) DEFAULT NULL',

		);
	}

	/**
   * 返回燕峰的混舱表结构
   * @return array             
	 */
	private function PriceResult(){
		return array(
			// copy form privateDTO(1)
			// Long id;
			'Id'         => 'bigint(20) NOT NULL AUTO_INCREMENT',
			// String routingType; 行程类型（OW ； RT）
			'RoutingType'=> 'char(2) NOT NULL',
			// String policyId;
			// Integer productType; 1.包机切位 2.申请 3.直销 4.清仓产品 6.特惠 
			'ProductType'=> 'tinyint(1) DEFAULT 1',
			// String tktAirline; 开票航空公司 
			'TktAirline' => 'char(3) NOT NULL',
			// Integer odType; 航路录入方式 单选（0:城市码 1:机场码）
			'OdType'     => 'tinyint(1) DEFAULT 1',
			// String dep; 出发地
			'Dep'        => 'char(3) NOT NULL',
			// String arr; 目的地
			'Arr'        => 'char(3) NOT NULL',
			// Integer direct; 是否直飞  1 是， 0 否
			'Direct'     => 'tinyint(1) DEFAULT 1',
			// String route; 线路
			'Route'      => 'varchar(300) NOT NULL',
			// String routing; 航路
			'Routing'    => 'varchar(300) DEFAULT NULL',
			// String routingClass;  舱位  舱位代码 ：  直飞航路只允许录入一个舱位 ；转机航路当每段的预订舱位不同时用-隔开，如Y-B;当两段的舱位一致时只需录入一个，如Y-Y只需录入Y即可  ；  舱位之间不可用/隔开 
			'RoutingClass'    => 'varchar(100) DEFAULT NULL',
			// String farebasis; 票价基础
			'Farebasis'       => 'varchar(10) NOT NULL',
			// String flightForbidden; 禁售航班
			'FlightForbidden' => 'varchar(300) DEFAULT NULL',
			// String flightAllow; 可售航班
			'FlightAllow'     => 'varchar(100) DEFAULT NULL',
			// Integer outboundStopoverAllow; 是否允许去程中途停留 单选（0：否  1：是），请录入0或1
			'OutboundStopoverAllow'      => 'tinyint(1) DEFAULT 0',
			// Integer inboundStopoverAllow; 是否允许回程中停留
			'InboundStopoverAllow'       => 'tinyint(1) DEFAULT 0',
			// String outboundDayTime; 去程班期
			'OutboundDayTime'            => 'varchar(20) NOT NULL',
			// Integer outboundDayTimeIndicator; 去程班期作用点 （0：第一国际段、1：始发航段、2：主航段、3：可为空）
			'OutboundDayTimeIndicator'   => 'tinyint(1) NOT NULL',
			// String inboundDayTime; 回程班期  1234567 作用在航段上， 12:00-14:00表示每天的12点到14点 ；12:00FRI-12:00SAT表示周五的中午12点至周六的中午12点
			'InboundDayTime'             => 'varchar(20) NOT NULL',
			// Integer inboundDayTimeIndicator; 回程班期作用点
			'InboundDayTimeIndicator'    => 'tinyint(1) NOT NULL',
			// String outboundTravelDate; 去程旅行日期
			'OutboundTravelDate'         => 'varchar(200) NOT NULL',
			// String outboundTravelDateExcept; 去程除外旅行日期
			'OutboundTravelDateExcept'   => 'varchar(200) NOT NULL',
			// Integer outboundTravelDateIndicator; 去程旅行日期作用点
			'OutboundTravelDateIndicator'=> 'tinyint(1) NOT NULL',
			// String inboundTravelDate; 回程旅行日期
			'InboundTravelDate'          => 'varchar(200) NOT NULL',
			// String inboundTravelDateExcept; 回程除外旅行日期
			'InboundTravelDateExcept'    => 'varchar(200) NOT NULL',
			// Integer inboundTravelDateIndicator; 回程旅行日期作用点
			'InboundTravelDateIndicator' => 'tinyint(1) NOT NULL',
			// String strictTravelDate; 取严旅行日期 
			'StrictTravelDate'           => 'varchar(200) DEFAULT NULL',
			// Integer strictTravelDateIndicator; 取严旅行日期作用点
			'StrictTravelDateIndicator'  => 'tinyint(1) DEFAULT NULL',
			// String saleDate;  销售日期
			'SaleDate'                   => 'varchar(200) NOT NULL',
			// Integer eligibility; 旅客资质 单选（0：普通成人、1：留学生、2：劳工 3：移民、4：海员）
			'Eligibility'                => 'tinyint(1) DEFAULT 0',
			// String minStay; 最短停留期
			'MinStay'                    => 'varchar(10) NOT NULL',
			// String maxStay; 最长停留期
			'MaxStay'                    => 'varchar(10) NOT NULL',
			// Integer minPax; 最小出行人数
			'MinPax'                     => 'tinyint(2) DEFAULT NULL',
			// Integer maxPax; 最大出行人数
			'MaxPax'                     => 'tinyint(2) DEFAULT NULL',
			// String fareType; 运价类型 单选（B2B、B2C、BSP、AMADEUS、ABACUS、GALILEO、SABRE、WORLDSPAN）
			'FareType'                   => 'varchar(10) NOT NULL',
			// Integer ticketType; 票种 单选（1：BSP电子票、2：航空公司本票电子客票）
			'TicketType'                 => 'tinyint(1) DEFAULT 1',
			// Integer price; 销售票面价
			'Price'                      => 'int(12) NOT NULL',
			// String currency; 币种 CNY
			'Currency'                   => 'varchar(3) NOT NULL',
			// Integer tax; 成人税费 不能含有小数点，默认币种为CNY
			'Tax'                        => 'int(12) DEFAULT NULL',
			// Integer taxYq; 燃油 不能含有小数点，默认币种为CNY
			'TaxYq'                      => 'int(12) DEFAULT NULL',
			// Integer taxQ; Q 不能含有小数点，默认币种为CNY
			'TaxQ'                       => 'int(12) DEFAULT NULL',
			// Integer chdPrice; 儿童价
			'ChdPrice'                   => 'int(12) DEFAULT NULL',
			// Integer chdTax; 儿童税费 不能含有小数点，默认币种为CNY
			'ChdTax'                     => 'int(12) DEFAULT NULL',
			// Integer chdTaxYq; 儿童税 不能含有小数点，默认币种为CNY
			'ChdTaxYq'                   => 'int(12) DEFAULT NULL',
			// Integer chdTaxQ; 儿童Q 不能含有小数点，默认币种为CNY
			'ChdTaxQ'                    => 'int(12) DEFAULT NULL',
			// String ticketingDeadline; 出票时限  举例:20-365,7,3/0-19,0,0表示起飞前20天以前的预订，至少于预订后7天内出票或起飞前3天前出票/起飞前20天内的预订，预定后立即出票（航班起飞日期,预订后多久出票,航班起飞前多久出票） 
			'TicketingDeadline'          => 'varchar(10) DEFAULT NULL',
			// Integer needpnr; 是否创建PNR 单选（0：否 1：是），请录入0或1
			'Needpnr'                    => 'tinyint(1) DEFAULT NULL',
			// Integer invoiceType; 报销凭证 单选（0：旅行发票  1：行程单 ），请录入0或1 
			'InvoiceType'                => 'tinyint(1) DEFAULT NULL',
			// String nationality; 适用乘客国籍 国家二字代码，可录入多个用/隔开表示或的关系，为空表示不限制   要做是否存在check
			'Nationality'                => 'varchar(2) DEFAULT NULL',
			// String excludeNationality; 除外乘客国籍 国家二字代码，可录入多个用/隔开表示或的关系,为空表示不限制     要做是否存在check   
			'ExcludeNationality'         => 'varchar(2) DEFAULT NULL',
			// String age; 乘客年龄 数字，可录入范围 如21-25表示21周岁至25周岁
			'Age'                        => 'tinyint(7) DEFAULT NULL',
			// Integer changeableOutbound; 去程可否更改 单选（0：否 1：是），请录入0或1
			'ChangeableOutbound'         => 'tinyint(1) DEFAULT 0',
			// String changeableOutboundFee; 去程改期费用 固定金额或百分比只可录入一种；当要录入百分比时币种必须为空默认是CNY
			'ChangeableOutboundFee'      => 'int(12) DEFAULT NULL',
			// String changeableOutboundCurrency;  去程改期币种 CNY
			'ChangeableOutboundCurrency' => 'varchar(3) DEFAULT NULL',
			// Integer changeableInbound; 回程可否更改 单选（0：否 1：是），请录入0或1
			'ChangeableInbound'          => 'tinyint(1) DEFAULT 0',
			// String changeableInboundFee; 回程改期费用 固定金额或百分比只可录入一种；当要录入百分比时币种必须为空默认是CNY
			'ChangeableInboundFee'       => 'int(12) DEFAULT NULL',
			// String changeableInboundCurrency; 回程改期币种 CNY
			'ChangeableInboundCurrency'  => 'varchar(3) DEFAULT NULL',
			// Integer refundableAll; 全部未使用可否退票 单选（0：否 1：是），请录入0或1
			'RefundableAll'              => 'tinyint(1) DEFAULT 0',
			// String refundableAllFee; 全部未使用退票费用 固定金额或百分比只可录入一种；当要录入百分比时币种必须为空默认是CNY 
			'RefundableAllFee'           => 'int(12) DEFAULT NULL',
			// String refundableAllCurrency; 全部未使用退票币种 CNY
			'RefundableAllCurrency'      => 'varchar(3) DEFAULT NULL',
			// Integer refundablePartlyused; 部分未使用可否退票 单选（0：否 1：是），请录入0或1
			'RefundablePartlyused'       => 'tinyint(1) DEFAULT 0',
			// String refundablePartlyusedFee; 部分未使用退票金额 固定金额或百分比只可录入一种；当要录入百分比时币种必须为空默认是CNY
			'RefundablePartlyusedFee'    => 'int(12) DEFAULT NULL',
			// String refundablePartlyusedCurrency; 部分未使用退票币种 CNY
			'RefundablePartlyusedCurrency'=> 'varchar(3) DEFAULT NULL',
			// Integer noshowChangeable; 是否允许NOSHOW改期，请录入0或1
			'NoshowChangeable'           => 'tinyint(1) DEFAULT 0',
		  // String noshowChangeableHours; //  改期时航班起飞前多久算NOSHOW	按小时计算，单位为H，如果是12小时，则录12H
		  'NoshowChangeableHours'      => 'varchar(20) DEFAULT NULL',
		  // String noshowOutboundChangeableFee; //  去程NOSHOW改期费用	固定金额或百分比只允许录入一种 费用包含去程改期费与Noshow罚金
		  'NoshowOutboundChangeableFee'=> 'int(12) DEFAULT NULL',
		  // String noshowOutboundChangeableCurrency;      //  去程改期币种 CNY
		  'NoshowOutboundChangeableCurrency' => 'varchar(3) DEFAULT NULL',
		  // String noshowInboundChangeableFee; //  回程NOSHOW改期费用	固定金额或百分比只允许录入一种 费用包含去程改期费与Noshow罚金
		  'NoshowInboundChangeableFee'       => 'int(12) DEFAULT NULL',
		  // String noshowInboundChangeableCurrency;      //  回程改期币种 CNY
		  'NoshowInboundChangeableCurrency'  => 'varchar(3) DEFAULT NULL',
		  // Integer noshowRefundableAll;     //  noshow全部未使用可否退票 单选（0：否 1：是），请录入0或1
		  'NoshowRefundableAll'        => 'tinyint(1) DEFAULT 0',
		  // String noshowRefundableHours;      //  航班起飞前多久算NOSHOW	按小时计算，单位为H，如果是12小时，则录12H
		  'NoshowRefundableHours'      => 'varchar(20) DEFAULT NULL',
		  // String noshowRefundableAllFee;      //  noshow全部未使用退票费用 固定金额或百分比只可录入一种；当要录入百分比时币种必须为空默认是CNY
		  'NoshowRefundableAllFee'     => 'int(12) DEFAULT NULL',
		  // String noshowRefundableAllCurrency;      //  noshow全部未使用退票币种 CNY
		  'NoshowRefundableAllCurrency'=> 'varchar(3) DEFAULT NULL',
		  // Integer noshowRefundablePartlyused;      //  noshow部分未使用可否退票 单选（0：否 1：是），请录入0或1
		  'NoshowRefundablePartlyused' => 'tinyint(1) DEFAULT 0',
		  // String noshowRefundablePartlyusedHours;      //  航班起飞前多久算NOSHOW	按小时计算，单位为H，如果是12小时，则录12H
		  'NoshowRefundablePartlyusedHours'   => 'varchar(20) DEFAULT NULL',
		  // String noshowRefundablePartlyusedFee;      //  noshow部分未使用退票金额 固定金额或百分比只可录入一种；当要录入百分比时币种必须为空默认是CNY
		  'NoshowRefundablePartlyusedFee'     => 'int(12) DEFAULT NULL',
		  // String noshowRefundablePartlyusedCurrency;      //  noshow部分未使用退票币种 CNY
		  'NoshowRefundablePartlyusedCurrency'=> 'varchar(3) DEFAULT NULL',
		  // String yourOfficeNo;      //  授权OFFICEN号 文本格式
		  'YourOfficeNo'               => 'varchar(20) DEFAULT NULL',
		  // String ticketingRemark;      //  出票备注 QTE需使用特使指令QTE：/XXXX
		  'TicketingRemark'            => 'varchar(1000) DEFAULT NULL',
		  // String outboundBaggage;      //  去程行李额 只允许录入两种格式：1.XX公斤   2.X件，每件XX公斤
		  'OutboundBaggage'            => 'varchar(4000) DEFAULT NULL',
		  // String inboundBaggage;      //  回程行李额 只允许录入两种格式：1.XX公斤   2.X件，每件XX公斤
		  'InboundBaggage'             => 'varchar(4000) DEFAULT NULL',
		  // Date gmtCreated;      //  创建时间
		  'GmtCreated'                 => 'int(13) NOT NULL',
		  // String site;      //  集团 
		  // String company;      //  公司
		  // String user;      //  操作员
		  // Integer status;      //  0-删除 1-审核 2-销售
		  'Status'                     => 'tinyint(1) DEFAULT NULL',
		  // String dataSource;      //  XSFSD,MANUAL,..
		  // String dataSourceOutboundId;      //  相关联的去程TOKEN
		  // String dataSourceInboundId;      //  相关联的回程TOKEN
		);
	}
}
