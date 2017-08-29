<?php
namespace admin\Controller;
use BYS\Controller;
class CtripController extends Controller {

    // 前端存储请求地址组合，后台接受routing 并储存至basis_routing表，标示 from ctrip
    // fsi 批量检验 routing 价格价格，没有问题的则录入 policy_result 表，标示 from ctrip
    // 交互：仅查询出发、到达、航空公司、即可获得政策及是否fsi检验，来源
	public function index(){

		// YYCNUS
		$outbound = array(
			'UA' => array( 'CTUSFO', 'PEKEWR', 'PEKIAD', 'PEKSFO', 'XIYSFO'),  // 'HGHSFO', 'PVGEWR', 'PVGLAX', 'PVGSFO' 杭州 上海暂时不做
			'DL' => array( 'PEKBOS', 'PEKDTW', 'PEKSEA', 'PVGATL', 'PVGDTW', 'PVGLAX', 'PVGLGA', 'PVGSEA', 'PVGSLC') 
		);
		// >= 10 
		$arrive   = array( 
			'UA' => array('SFO', 'IAD', 'BOS', 'LAX', 'EWR', 'ORD', 'MCO', 'IAH', 'SLC', 'DEN', 'CVG', 'LAS', 'PIA', 'ATL', 'RDU', 'BDL', 'BTV', 'SEA', 'SAN', 'CLT', 'BNA', 'DFW', 'PIT', 'DSM', 'CLE', 'BOI', 'MIA', 'SGF', 'STL', 'ROA', 'YYZ', 'PHL', 'OMA', 'BUF', 'PDX', 'MSP', 'OKC', 'HSV', 'PHX', 'IND', 'DCA', 'SBN', 'LGA', 'SAT', 'CMH', 'LAN', 'MCI', 'JAC', 'CMI', 'EUG', 'AUS', 'MEX', 'SYR', 'FLL', 'GEG', 'DTW', 'ALB', 'RIC', 'YUL'),
			'DL' => array('SEA', 'DTW', 'BOS', 'LAX', 'MCO', 'SLC', 'ATL', 'DEN', )
		);

		// 航路
		$routing = array();

		// 初始化
		// $range 航程
		foreach($outbound as $aircompany => $range){
			$routing [$aircompany] = array();
			// cn to us
			foreach ($range as $outRange) {
				$cn = substr($outRange, 0, 3);
				$us = substr($outRange, 3, 3);

				// us to us
				foreach ($arrive[$aircompany] as $destination) {
					if($us == $destination){
						array_push($routing[$aircompany], array(
							'depart'      => $cn,
							'transfer'    => '',
							'arrive'      => $destination,
						));
					}else{
						array_push($routing[$aircompany], array(
							'depart'      => $cn,
							'transfer'    => $us,
							'arrive'      => $destination,
						));					
					}
				}
			}
		}

    // 携程低价中的航路
    import('vender/api/OpenApi.class.php');
    $api = new \Api\OpenApi;
    $url = "https://Intlflightapi.ctrip.com/Intlflightapi/LowPrice.asmx?WSDL";
    $ctripOutboundTravelDate = date('Y-m-d', time()+24*60*60); // 明天 
    $ctripInboundTravelDate  = date('Y-m-d', time()+7*24*60*60); // 明天+ 7 
    $result = array();

    foreach ($routing as $aircompany => $range) {
			for($i = 0; $i < 1; $i ++){
		    $query = array(
		        'TripType'=> 'RT',// OW: RT
		        'DepartCity'=> $range[$i]['depart'],
		        'ArriveCity'=> $range[$i]['arrive'],
		        'Owner'=> $aircompany,
		        'SeatGrade'=> 'Y', // F头等舱:C公务舱:W:超级经济舱:Y经济舱
		        'OutboundTravelDate'=> $ctripOutboundTravelDate, 
		        'InboundTravelDate'=> $ctripInboundTravelDate, 
		        'ProductType'=> 'ALL', // PRIFARE : PUBFARE : ALL
		        'PassengerNum'=> '1', 
		        'PassengerEligibility'=>'ADT', // ADT普通: STU学生: LAB劳工: EMI移民: SEA海员
		        'IsHasTax'=> 'False', // True: False
		        'LowPriceSort'=> 'TripType' // Price 外放总价: TripType 行程
		        );
		    \BYS\Report::p($query);

		    $xml = $api->requestXML(9, $query, $url);
		    array_push($result, simplexml_load_string($xml));
		    ob_flush();
		    flush();
		    sleep(10);

			}
		}
		\BYS\Report::p($result );
		// ctrip 请求间隔 10s
	}
}
