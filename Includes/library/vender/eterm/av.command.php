<?php
include 'eterm.class.php';
class AV extends eterm{
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
} 
?>