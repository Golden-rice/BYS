<?php
use Eterm\Eterm;
class Fsl extends Eterm{
	private $arr = array();      // 解析后的结果

	public function analysis($switch = array(), $fliter = array()){
    if(!empty($fliter)) $this->fliter = $fliter;

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
        // 如果没数据
        if(preg_match("/NO\sFARE\sFOR\sTHIS/", $pageline, $arr))
          return array();

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
    // \BYS\Report::log('无fsd结果');

		foreach ($dataList[1] as $pageNum => $Page) {
	    if($pageNum == 0){
        $totalPageList = array_merge($totalPageList, $this->callArr($Page, true));
      }else{
        $totalPageList = array_merge($totalPageList, $this->callArr($Page, false));
      }
  	} 

    if(empty($totalPageList)) {
      \BYS\Report::log('无运价结果');
      return;
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
    if(empty($this->arr)) return;
  	// 匹配
  	// 拆分航路
  	// 城市：SFO 旧金山 CHI 芝加哥 （ORD MDW） NYC 纽约（EWR JFK LGA） WAS 华盛顿（IAD DCA） LAX 洛杉矶  
  	// $matchCity = array('SFO', 'CHI', 'NYC', 'WAS', 'LAX', 'ORD', 'EWR');
  	// 合并所有中转情况
  	$result = array();
  	$rangePotResult = array(); // 所有的中转
  	$end = '';
    if(!empty($this->fliter) && isset($this->fliter['aircompany'])) {
      $fliter_aircompany = $this->fliter['aircompany'];
      $cnto = model('cn_to');

      $fliter_stay = array(); //  所有的中国出发直达的目的地，二维数组
    }


  	// 解析所有行的中转情况
  	foreach ($this->arr as $key => $line) {
      // 每条航路
  		$range    = explode('-',$line);
      // 航路出发
  		$depart   = $range[0];
      // 所有匹配的中转点，已去重
  		$rangePot = array();
      // 航路到达
  		$arrive   = end($range);
  		// 放到上层
  		$end      = $arrive;

      // 筛选中转点：中国各个城市出发匹配的中转点
      if(isset($cnto) && (!isset($fliter_stay[$depart])) ){
        // 转换成机场代码
        $fliter_direct = $cnto->query("SELECT
                C.aircompany,
                C.depart,
                C.arrive,
                C.departCity,
                D.ACC_CityCode AS arriveCity
              FROM
                (
                  SELECT
                    B.CNTo_Aircompany AS aircompany,
                    B.CNTo_Depart AS depart,
                    B.CNTo_Arrive AS arrive,
                    A.ACC_CityCode AS departCity
                  FROM
                    basis_cn_to AS B
                  LEFT JOIN basis_airport_city_code AS A ON B.CNTo_Depart = A.ACC_Code
                  WHERE
                    B.CNTo_Aircompany = '{$fliter_aircompany}'
                  AND A.ACC_CityCode = '{$depart}'
                ) AS C
              LEFT JOIN basis_airport_city_code AS D ON C.arrive = D.ACC_Code ;");

        $fliter_stay[$depart] = array();

        if ( $fliter_direct ) 
          foreach ($fliter_direct as $item) {
            // 机场代码
            if( isset($item['arriveCity']) && !in_array($item['arriveCity'], $fliter_stay[$depart]))
              array_push($fliter_stay[$depart], $item['arriveCity']);
          }
      }

  		foreach ($range as $r){
  			if($r != $arrive && preg_match_all("/SFO|CHI|NYC|WAS|LAX|ORD|EWR/", $r, $arrivePot)){
  				if(!empty($arrivePot[0])){
  					foreach ($arrivePot[0] as $pot) {
              // 即不再数组中，又在从中国出发的匹配城市中  						
              if (isset($fliter_stay[$depart]) && !empty($fliter_stay[$depart])){
                if( !in_array($pot, $rangePot) && in_array($pot, $fliter_stay[$depart]) )
  	  						array_push($rangePot, $pot);
              }
              else{
                if( !in_array($pot, $rangePot) ) 
                  array_push($rangePot, $pot);
              }
  					}
  				}
  			}
  		}

  		$routing = $depart.'-';
  		if(!empty($rangePot)){
	  		foreach ($rangePot as $pot) {
	  			$routing .= $pot.'/'; 
	  		}
	  		// 汇集所有中转情况
  			array_push($rangePotResult, $rangePot);
  		}
	  	else
	  		$routing = rtrim( $routing, '-' );

  		$routing = rtrim( $routing, '/' ).'-'.$arrive;
  		// echo '原航路：'.$line."<br>解析后：".$routing."<br>";
  		// echo "<hr>";
  		
  		$this->arr[$key] = array(
  			'source'    => $line,
  			'translate' => $routing,
  		);
  	}

  	// 合并所有的中转点
  	if(!empty($rangePotResult))
	  	foreach ($rangePotResult as $rangePotItem) {
	  		foreach ($rangePotItem as $pot) {
		  		if(!in_array($pot, $result) && $end != $pot )
		  			array_push($result, $pot);
	  		}
	  	}

  	// 合并结果
  	$this->arr['result'] = $result;
  }
}