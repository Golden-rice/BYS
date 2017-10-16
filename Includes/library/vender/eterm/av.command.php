<?php
use Eterm\Eterm;
class Av extends Eterm{
	private $arr = array();      // 储存数组信息
	public function analysis($switch){
		// 解析文件，并返回解析后的结果
  	if(empty($switch)){
    	$switch = array(1); // 解析参数：1.格式化 
  	}
  	foreach ($switch as $flags) {
  		switch ($flags) {
  			case 1: // 1. 获得单页数据
  				$this->getOne($this->tmp);
  			  continue;
  			case 2: // 2. 解析单页数据
  				$this->mkList($this->arr);
  			default:
    			break;
  		}
  	}

  	return $this->arr;
	}
	private function getOne($fileName){
		$f_page = parent::initFile($fileName, 0, 1);
		$this->arr = $f_page;
	}
	private function mkList($array){
		$arr = array();
		foreach($array as $n => $dataline){
			$from = substr($dataline, 0, 3);  
			if($from == ' AV' || $from ==  'DEP'){
				continue;
			}else if( $from == 'TOT'){
				$arr['total'] = substr($dataline, 20, 24);
				break;
			}else if($from == '**I'){
				break;
			}
			else{
				$arr[$n] = array();
				$arr[$n]['start'] = substr($dataline, 0, 3);
				$arr[$n]['startTime'] = substr($dataline, 4, 4);
				$arr[$n]['end'] = substr($dataline, 11, 3);
				$arr[$n]['endTime'] = substr($dataline, 15, 4);
				$arr[$n]['week'] = substr($dataline, 22, 3);
				$arr[$n]['fly'] = substr($dataline, 26, 5);
				$arr[$n]['group'] = substr($dataline, 33, 5);
				$arr[$n]['team'] = substr($dataline, 40, 3);
				$arr[$n]['airtype'] = substr($dataline, 46, 4);
				$arr[$n]['meal'] = substr($dataline, 51, 4);
				$arr[$n]['distance'] = substr($dataline, 57, 8);
				$arr[$n]['ope'] = substr($dataline, 66, 6);
			}
		}
		$this->arr = $arr;
	}

	// AV:UA850/06OCT 解析指定航班及日期的舱位
	public function parseSrource($departAndArrive){
		$hasPn     = preg_match('/PAGE[\s]*(\w+)\/(\w+)/', $this->tmp);
		$fkey      = -1;
		$pattern   = "/$departAndArrive/";
		$depart    = '';
		$arrive    = '';
		if(preg_match("/没有资源。/is", $this->tmp)) {
			\BYS\Report::log($this->tmp);
			return;
		} 

		$cabin_src = parent::fromToArray($this->tmp, 0, 1);
		foreach ($cabin_src as $src_key => $src_val) {
			if($fkey < 0 && preg_match($pattern, $src_val)){
				$fkey = $src_key;
				break;
			}
		}
		if($fkey <0 && $hasPn){
			parent::getAllPage($this->tmp, 'xs/fspn', 'w');
			$cabin_src = parent::fromToArray($this->tmp, 0, 1);
			foreach ($cabin_src as $src_key => $src_val) {
				if($fkey < 0 && preg_match($pattern, $src_val)){
					$fkey = $src_key;
					break;
				}
			}
		}
		
		if($fkey <0){
			\BYS\Report::log($this->tmp);
			return;
		}

		if(!isset($cabin_src[$fkey+1]))
			$cabin  = preg_replace('/\s{2,}/', ' ', $cabin_src[$fkey]);
		else
			$cabin  = preg_replace('/\s{2,}/', ' ', $cabin_src[$fkey].$cabin_src[$fkey+1]);

		$depart = substr($cabin, 0, 3);
		$arrive = substr($cabin, 3, 3);
		preg_match_all('/(?P<cabin>\w)(?P<num>\d|A)/', $cabin, $cabin_array);
		$cabin_result = array();
		if(isset($cabin_array['cabin'])){
			foreach ($cabin_array['cabin'] as $k => $c) {
				$cabin_result[$c] = $cabin_array['num'][$k];
			}
		}
		return array('depart'=>$depart, 'arrive'=>$arrive, 'cabin'=>$cabin_result);
	}
} 
?>