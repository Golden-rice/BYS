<?php
use Eterm\Eterm;
class Avh extends Eterm{
	private $data;                     // 录入数据
	private $arr = array();            // eterm返回数据
	private $arr_allow_seat = array(); // 航空公司下的舱位对照表


  public function analysis($switch){
    // 解析文件，并返回解析后的结果
  	if(empty($switch)){
    	$switch = array(1,2,6); 
  	}
  	$arr_result = array();
  	foreach ($switch as $flags) {
  		switch ($flags) {
  			case 1:
					$this->initDate($this->tmp);
					continue;
				case 2:
					$this->to2Arr($this->arr);
					continue;
				case 6:
					$this->display($this->arr);
					continue;
  			default:
  				break;
  		}
  	}

    // parent::p($this->arr);
		return $this->arr;
  }
  public function readSource(){
  	return $this->source;
  }
	private function initDate($fileName){
		$arr = parent::initFile($fileName, 0, 1);
		$this->arr = preg_grep("/\*\*\s/", $arr, PREG_GREP_INVERT);
	}   

	private function to2Arr($arr){
		// 按照航程划分，返回二维数组
		$arr_date = array(); // 存放同一天数组

  	for ($i=0; $i < count($arr); $i++) {  // 逐条遍历
  		if(substr($arr[$i],0,1) == " " && substr($arr[$i],1,1) != ' '){
  			// $curDate = substr($arr[$i], 1, 10); // 带星期
  			$curDate = substr($arr[$i], 1, 5); // 不带星期
  		}
  		$j = $i+1;

  		if(!isset($arr[$j]) || !strlen($arr[$j]) >2 ) continue;

  		if(substr($arr[$j],0,1) !=" " && $j < count($arr)){
  			$cur = substr($arr[$j], 0, 1);

    		$arr_date[$curDate][$cur][] = $arr[$j];
    		$j++;

    		while(isset($arr[$j]) && substr($arr[$j], 0, 2) == '  '){
    			$arr_date[$curDate][$cur][] = $arr[$j];
    			$j++;
    		}
  		}
  	}

		$this->arr = $arr_date;
	}


	private function partFormat($part){
		$arr = array();
		$arr["airCompany"] = substr($part, 4, 2);  // 航司 4 ->2
		$arr["start"] = substr($part, 47, 3);      // 出发 47->3=50
 		$arr["end"] = substr($part, 50, 3);        // 到达 50->3=53
		$arr["flight"] = substr($part,4,6);        // 航班 4->6=10
		$arr["dateFrom"] = substr($part,12,3);     // 数据直连 12->3=15
		$arr["startTime"] = substr($part,54,4);    // 出发时间 54->4=58
		$arr["endTime"] = substr($part,61,6);      // 到达时间 60->4=64
		$arr["flightTime"] = substr($part,154,5);  // 飞行时间 153->5
		$arr["airType"] = substr($part,68,3);      // 机型 67->3=70
		$arr["carrier"] = substr($part,84,6);      // 承运 83->6=89
		$arr["seat"] = array();                    // 舱位 16->10*3=46  95->16*3=143
		for ($i=0; $i < 10; $i++) {
 			if (substr($part, 16+$i*3,1) != " ") {
	 			$arr["seat"] [ substr($part, 16+$i*3,1) ] = substr($part, 17+$i*3,1);
 			 } 
 			}
 		for ($j=0; $j < 16; $j++) { 
 			if(substr($part, 96+$j*3,1) != " "){
	 			$arr["seat"] [ substr($part, 96+$j*3,1)] = substr($part, 97+$j*3,1);
 			}
 		}


 		return $arr;
	}
	public function display($arr){
		// 展示数据
		if(empty($arr)) {
			return;
		} 
		// 根据航空公司匹配舱位及设置舱位等级
		// $seatArr = $this->getSeat(array("airCompany"=>$this->data['airCompany']),'2');
		
		$arr_tmp = array();
		foreach ($arr as $date => $li){
			foreach($li as $part => $airLine){
		 		foreach ($airLine as $key=>$airData) {  
		 			$arrData = $this->partFormat($airData); 
		 			
		 			$arr_tmp[$date][$part][$key] = array(
		 				'start' => preg_replace("/\s/", "", $arrData["start"]), // $arrData["start"] == "   "? "" : $arrData["start"]
		 				'end'   => $arrData["end"],
		 				'flight' => preg_replace("/\s/", "", $arrData["flight"]),
		 				'dateFrom' => preg_replace("/\s/", "", $arrData["dateFrom"]), // $arrData["dateFrom"] == "   " ? "" : $arrData["dateFrom"]
		 				'startTime' => substr($arrData["startTime"], 0, 2).":".substr($arrData["startTime"], 2),
		 				'endTime' => substr($arrData["endTime"], 0, 2).":".substr($arrData["endTime"], 2),
		 				'flightTime' => $arrData["flightTime"] == false ? "" : $arrData["flightTime"],
		 				'airType' => $arrData["airType"],
		 				'carrier' =>  preg_replace("/\s/", "", $arrData["carrier"]), // $arrData["carrier"] == "      " ? "" : $arrData["carrier"]
		 				'startDate' => substr($date, 0, 5),
		 				'startWeek' => substr($date, 6, 7),
		 				'cabin' => $arrData["seat"]
		 			);


		 		}
	 		}
	 	}
	 	$this->arr = $arr_tmp;
	}

	private function getAirlineIndex($airline){
		$index = '';
		foreach ($airline as $key => $value) {
			if(strlen($key)>10){
				$index = $key;
				break;
			}
		}
		return $index;
	}

}

?>