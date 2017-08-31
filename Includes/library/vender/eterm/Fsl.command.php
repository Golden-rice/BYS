<?php
use Eterm\Eterm;
class Fsl extends Eterm{
	private $arr = array();      // 解析后的结果

	public function analysis($switch = array()){
		// 根据选项执行
  	foreach ($switch as $flags) {
  		switch ($flags) {
  			// 请求下一步动作
  			case 1:
  				$this->tofsl();
					continue;
				case 2:
					parent::getAllPage($this->tmp, 'xs/fspn');
					continue;
				case 3:
					$this->readAllFsl($this->tmp);
					continue;
				case 4:
					$this->format();
	  		default:
	  			break;
  		}
  	}

  	return $this->arr;
	}

	private function tofsl(){
		// fsd 请求
		if(count( parent::initFile($this->tmp)) >11){
			$this->command('XS/FSL1', 'w');
		}else{
			echo '无fsd结果';
		}
	}

	private function callArr($page, $isF){
		// 传递该页page所含舱位，判断是否为第一页
		$pageArr = explode("\r",$page);
		// 去除最后两行：页码和提示符
		$pageArr = array_slice($pageArr, 0, count($pageArr)-2);

		$fkey = 0;
		if($isF){ // 如果是第一页
    	foreach ($pageArr as $key => $pageline) {
    		// 确定开始匹配的位置
    		if( $fkey == 0 && preg_match("/\s\d\*\w/", substr($pageline, 0, 10), $arr)) {
    			$fkey = $key; // 开始匹配舱位的位置，通常是4
          break;
    		}
    	}
    	// 重置
    	$pageArr = array_slice($pageArr, $fkey, count($pageArr));
		}
		return $pageArr;
	}

	private function readAllFsl($datafrom){
    $data = $datafrom;
    preg_match_all("/\[CDATA\[(.*?)\]\]/is", $data, $dataList);
  	$totalPageList = array();
		foreach ($dataList[1] as $pageNum => $Page) {
	    if($pageNum == 0){
        $totalPageList = array_merge($totalPageList, $this->callArr($Page, true));
      }else{
        $totalPageList = array_merge($totalPageList, $this->callArr($Page, false));
      }
  	} 

  	// 将所有的设为一行
		$curFsl = '';
		foreach ($totalPageList as $key => $line) {
			// 初始化临时储存Fsl数据
			if( preg_match("/\s\d{1,2}\*\w/", substr($line, 0, 15), $arr) ){
				if($curFsl != ''){
					array_push($this->arr, preg_replace(array('/\s/','/\d{1,2}\*/'), array('',''), $curFsl));
					$curFsl = '';
				}
				$curFsl = rtrim($totalPageList[$key], "\r");
			}
			else
				$curFsl .= rtrim($totalPageList[$key], "\r");
		}
	}

	public function readSource(){
		$this->getAllPage($this->tmp, 'xs/fspn');
  	return $this->source;
  }

  private function format(){
  	// 匹配
  	// 拆分航路
  	// 城市：SFO 旧金山 LAX 洛杉矶 
  	$matchCity = array('SFO', 'CHI', 'EWR', 'WAS', 'LAX');
  	foreach ($this->arr as $key => $line) {
  		$routing  = explode('-',$line);
  		$end      = end($routing);
  		$endMatch = explode('/',$routing[count($routing)-2]);
  		foreach ($endMatch as $match) {
	  		echo $end .'->'.$match."<br>";
  		}
  	}
  }
}