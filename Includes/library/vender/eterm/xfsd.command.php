<?php
include 'eterm.class.php';
class XFSD extends Eterm{
	private $arr = array();      // 储存数组信息
    private $org_arr = array();  // 原始数组信息
	private $date;
    private $startKey;           // 开始匹配的起始位置
	public function p($a){
		parent::p($a);
	}
    public function analysis($switch){
    	// 解析文件，并返回解析后的结果
    	if(empty($switch)){
	    	$switch = array(1,2,3); // 解析参数：1.格式化 
    	}
    	foreach ($switch as $flags) {
    		switch ($flags) {
    			case 1:
    				// 获得全部页数
 					$this->getAllPage($this->tmp, 'xs/fspn');
 					continue;
 				case 2:
    				// 获得全部舱位
 					$this->getAllSeat($this->tmp);
 					continue;
 				case 3:
    				// 更新成关联数组
 					$this->display($this->arr);
 					continue;
                case 4:
                    // 更新成关联数组
                    $this->change_item_1();
                    continue;
    			default:
    				break;
    		}
    	}
    	// parent::p($this->arr);

        $result = $this->arr;
        $this->arr = array();
        
		return $result;
    } 

    public function fare($switch, $date, $command){
    	// 解析政策文件，并返回解析后的结果
    	$this->date = $date;
        $this->command = $command;

    	if(empty($switch)){
	    	$switch = array(0,1,2,3); 
    	}
    	// if($this->noFare($this->tmp)){ 
    	// 	echo '无Fare数据';
    	// 	return; 
    	// }
    	foreach ($switch as $flags => $value) {
    		switch ($flags) {
    			case 0:
    				// 跳转某项目, 回填tmp文件，回填时判断能否
    				$this->fsn1($switch[$flags]);
 					continue;
    			case 1:
    				// 获得全部页数
 					$this->getAllPage($this->tmp, 'xs/fspn');
 					continue;
 				case 2:
 					// 去除无关信息
 					$this->f_fliter_useless($this->tmp);
                    continue;
    			default:
    				break;
    		}
    	}

        if(!empty($this->arr)){
           return $this->arr;
        }else{
            echo '无数据';
        }
    }

    private function f_fliter_useless($fileName){
    	$arr_tmp = parent::initFile($fileName, 0, 1);
    	// parent::p($arr_tmp);
    	$fliter_str_1 = '/PAGE[\s]*(\w+)\/(\w+)/';               // 匹配页所在行
    	$fliter_str_2 = '/\<\</';                                // 匹配<<
    	$fliter_str_3 = "/FSN.*{$this->date['startDate']}/i";    // 匹配分页前缀
    	$fliter_str_4 = '/\*+\s+END\s+\*+/';                     // 去除**end**结尾
    	$fliter_str_5 = "/(\d){2}\.[A-Z]+([-|\/|\s]+[A-Z]+)?/i"; // 匹配序号
    	$fliter_str_6 = "/\d+\s+{$this->date['fare']}\s+\/?/i";  // 匹配fare

    	foreach ($arr_tmp as $key => $line) {
    		if(preg_match_all($fliter_str_1, $line, $a)){
    			unset($arr_tmp[$key]);
    			continue;
    		}
    		if(preg_match_all($fliter_str_2, $line, $a)){
    			$arr_tmp[$key] = preg_replace($fliter_str_2, '  ', $arr_tmp[$key]);
    			continue;
    		}
    		if(preg_match_all($fliter_str_6, $line, $a)){
    			unset($arr_tmp[$key]);
    			continue;
    		}
    		if(preg_match_all($fliter_str_3, $line, $a)){
    			if(preg_match_all('/D\s+\d+/', $arr_tmp[$key+3], $a)){  // 如果带有D 123类型
    				unset($arr_tmp[$key+3]);
    			}
    			unset($arr_tmp[$key]);
    			unset($arr_tmp[$key+1]);
    			continue;
    		}
    		if(preg_match_all($fliter_str_4, $line, $a)){
    			unset($arr_tmp[$key]);
    			continue;
    		}


    	}
    	// 重置数组，查找序号，并分块
		$arr_tmp = array_values($arr_tmp);
    	$length = count($arr_tmp);
    	$arr_block = array();
    	foreach($arr_tmp as $key => $line){
    		if(preg_match_all($fliter_str_5, $line, $a)){
    			$start = $key; 
    			$arr_block["{$a[0][0]}"] = array();
    			for($i = $key+1; $i< $length; $i++){
    				
    				if(preg_match_all($fliter_str_5, $arr_tmp[$i], $b)){  // 结尾
    					// $end = $i-1;
    					break;
    				}
        //             else if($i == $length-1){
    				// 	$end = $length-1;
    				// }
    				// array_push($arr_block["{$a[0][0]}"], $arr_tmp[$i]);
                    $str .= "\r".$arr_tmp[$i];
                    $arr_block["{$a[0][0]}"] = $str;
    			}
                $str = '';
    			// echo $start.'-'.$end.':'.$a[0][0].'<br>';
    			continue;
    		}
    	}
    	$this->arr = $arr_block;
    }

    private function fsn1($index){
        // 文件读取xs/fs后，如果文件小于10K执行fsn1，查看某项
        $command_1 = $this->command;
		if( empty($index) ){
			$command_2 = 'xs/fsn1'; 
		}else{
			$command_2 ='xs/fsn1//'.$index; 
		}
        $commandArr = array($command_1=>0, $command_2=>1);
        parent::mixCommand($commandArr,'w');	
    }

    public function removeFareRuntime($command){

        preg_match_all('/\/#[\w]?\*?(\w+)\/\/\//', $command, $str);
        preg_match_all('/\/(\w{2})\//', $command, $str2);

        $fare = $str[1][0];
        $aircompany = $str2[1][0];

        parent::removeRuntime($fare.$aircompany);
    }

    public function callbak($switch){
    	// 解析中文退改政策文件，返回解析后结果
    	 if(empty($switch)){
	    	$switch = array(1,2,3); // 解析参数：1.格式化 
    	}
    	foreach ($switch as $flags) {
    		switch ($flags) {
    			case 1:
    				// 去除空格和无关行
    				$this->cl_reset($this->tmp);
 					continue;
 				case 2:
    				// 将数组封装成关联数组
    				$this->cl_setArr($this->arr);
 					continue;
    			default:
    				break;
    		}
    	}
    	parent::p($this->arr);
    	return $this->arr;
    }

    private function cl_reset($fileName){
    	// 格式化中文文件，并返回结果
    	$page = parent::initFile($fileName, 0, 1);
    	foreach ($page as $key => $value) {
    		if(empty($value)){
    			unset($page[$key]);
    		}else{
    			$page[$key] = preg_replace('/\s+|\./','',$page[$key]);
    		}
    	}
		$this->arr = $page;
    }

    private function cl_setArr($arr){
    	// 默认项目值为下一行
    	$arr_tmp = array();
    	foreach ($arr as $key => $value) {
    		if(preg_match('/最短停留/',$value)){
    			$arr_tmp['short_stay'] = $arr[$key+1];
    			continue;
    		}
    		if(preg_match('/最长停留/',$value)){
    			$arr_tmp['long_stay'] = $arr[$key+1];
    			continue;
    		}
    		if(preg_match('/退票/',$value)){
    			$arr_tmp['bak_ticket']['start'] = $arr[$key+1];
    			preg_match('/\d+%?/',$arr[$key+2],$reg);
    			$arr_tmp['bak_ticket']['content'] = $reg[0];
    			continue;
    		}
    		if(preg_match('/更改/',$value)){
    			$arr_tmp['change_ticket']['start'] = $arr[$key+1];
    			preg_match('/\d+%?/',$arr[$key+2],$reg);
    			$arr_tmp['change_ticket']['content'] = $reg[0];
    			continue;
    		}
    		if(preg_match('/误机/',$value)){
    			$arr_tmp['noshow_ticket']['start'] = $arr[$key+1];
    			$arr_tmp['noshow_ticket']['content'] = $arr[$key+2];
    			continue;
    		}
    	}
    	$this->arr = $arr_tmp;
    }

	private function getAllPage($fileName, $command){
		// 获取除起一页的其他页数据
		$f_page = parent::initFile($fileName, 0, 1);
		preg_match_all('/PAGE[\s]*(\w+)\/(\w+)/',end($f_page), $str); // 获取页码
		$pageTotal = intval($str[2][0]);   // 总页码
    	$pageCur = intval($str[1][0]);     // 当前页码

    	while($pageCur < $pageTotal){
    		parent::addCommand($command ,'a'); // 回填tmp文件
    		$pageCur++;
    	}
	}
	private function inputSeat($pageline){
		if(intval(substr($pageline, 0, 3)) > 99){
			$pos = 3;
		}else{
			$pos = 2;
		}
		if(substr($pageline, $pos,1) ==' '){  // 去除数据中混入日期
			if(substr($pageline,0,1) == "\r"){
				array_push($this->arr, substr($pageline,1));
			}else{
				array_push($this->arr, substr($pageline,0));
			}
		}
	}
	private function getSeat($page, $isF){
		// 传递该页page所含舱位，判断是否为第一页
		$pageArr = explode("\r",$page);
		$pageArr = array_slice($pageArr, 0, count($pageArr)-2);

		if($isF){ // 如果是第一页
			$fkey = 0;
	    	foreach ($pageArr as $key => $pageline) {
	    		if( !$fkey && preg_match_all("/[\d]{2}[\s]{1}\w/",substr($pageline,0, 10),$arr)) {
	    			$fkey = $key-1; // 开始匹配舱位的位置，通常是12
                    $this->startKey = $pageArr[$fkey];
	    		}
	    		if(!empty($fkey) && $key > $fkey ){
					if(substr($pageArr[$key+1], 0,4) == '    ' ){  // 带有星期
						$pageline = $pageline.substr($pageArr[$key+1], 1);
						$this->inputSeat($pageline);
					}else if(substr($pageArr[$key], 0,4) != '    '){
						$this->inputSeat($pageline);
					}		    		
	    		}
	    	}
		}else{
			$pageArr = array_slice($pageArr, 1, count($pageArr));
			foreach ($pageArr as $key => $pageline) {
				if(substr($pageArr[$key+1], 0,4) == '    ' ){  // 带有星期
					$pageline = $pageline.substr($pageArr[$key+1], 1);
					$this->inputSeat($pageline);
				}else if(substr($pageArr[$key], 0,4) != '    '){
					$this->inputSeat($pageline);
				}
				
			}
		}
	}
	private function getAllSeat($fileName){
		$file = file_get_contents($fileName);
        // $file = $fileName;
        
    	preg_match_all("/\[CDATA\[(.*?)\]\]/is", $file, $newFile);
		foreach ($newFile[1] as $pageNum => $Page) {
    		if($pageNum == 0){
    			$this->getSeat($Page, 1);
    		}else{
    			$this->getSeat($Page, 0);
    		}
    	} 
        $this->org_arr = $this->arr;   	
	}

    private function display($arr){
        // update by xiaojia

        preg_match('/\s(\w{3})(\w{3})\/(\w{2})/',$this->startKey, $str);
        $start = $str[1];
        $end = $str[2];
        $direction = $str[3];
        foreach($arr as $key => $dataline){
            preg_match_all("/\/?\s+([0-9]*\.?[0-9]{0,2})\s*\/[A-Z]{1}/is",$dataline,$res);
            $index = substr($dataline, 0, 3);                  // 序号
            $pos = 3;
            if(intval($index) > 99){
                $pos = 4;
            }
            $fare = preg_replace('/\s*/', "", substr($dataline, $pos, 8));                // fare 
            $special = substr($dataline, $pos+9,1);            // 特殊规则
            preg_match_all("/ADVP\s*([0-9]{1,2}D)/",$dataline, $advp);
            $ADVPDay = $advp[1][0];             // ADVP 

            if(substr($dataline, $pos+23, 6) != "      "){
                $singleLineFee = "";
                $backLineFee = $res[1][0]?$res[1][0]:$res[1][1];   // 往返价格 //substr($dataline, $pos+23, 6);
            }else{
                $singleLineFee = $res[1][0]?$res[1][0]:$res[1][1];
                $backLineFee = "";
            }      
            $seat = substr($dataline, $pos+30, 1);             // 舱位
            $minStay = substr($dataline, $pos+32, 3);          // 最低滞留时间
            $maxStay = substr($dataline, $pos+36, 3);          // 最长滞留时间
            $allowDateStart = substr($dataline, $pos+40, 5);   // 适用截止起始日期
            $allowDateEnd = substr($dataline, $pos+46, 5);     // 适用截止结束日期
            $reTicket = substr($dataline, $pos+52, 5);         // 退票规则
            preg_match_all('/[D]\s\d+/', substr($dataline, $pos+58), $arrWeek);
            $allowWeek  = $arrWeek[0][0] == NULL? '1234567':substr($arrWeek[0][0],2);            // 可用周期
            
            // 回填数据
            $this->arr[$key] = array(
                'index'=>$index, 
                'fare'=>$fare, 
                'special'=>$special=='*'?'YES':'NO',
                'ADVP'=>$ADVP ? $ADVP :'', 
                'ADVPDay'=>$ADVPDay ? $ADVPDay :'', 
                'singleLineFee'=>preg_replace("/\s/", "", $singleLineFee), //str_replace('      ','',$singleLineFee)
                'backLineFee'=>$backLineFee, 
                'seat'=>$seat, 
                'minStay'=>preg_replace("/\s/", "", $minStay), // str_replace('   ','',$minStay)
                'maxStay'=>preg_replace("/\s/", "", $maxStay), // str_replace('   ','',$maxStay)
                'allowDateStart'=>preg_replace("/\s/", "", $allowDateStart), // str_replace('     ','',$allowDateStart)
                'allowDateEnd'=>preg_replace("/\s/", "", $allowDateEnd), // str_replace('     ','',$allowDateEnd)
                'reTicket'=>$reTicket, 
                'allowWeek'=>$allowWeek,
                'start' => $start,
                'end' => $end,
                'direction' => $direction
            );
        }
    }


    private function change_item_1(){
        // parent::p($this->org_arr);
        foreach($this->org_arr as $key => $dataline){
            preg_match_all('/[ADVP]\s\d+\w+/', substr($dataline, $pos+66), $arrADVP);
            $this->arr[$key]['ADVPDay'] = $arrADVP[0][0] ? substr($arrADVP[0][0],2) : ""; 
            // $this->arr[$key]['reTicket'] = '';
            
        }
        // parent::p($this->arr);

    }
	private function fliter_date($arr){
		// 筛选日期计算公式：查询日期>最低滞留时间+起始截止日期，不合法的删除该数据
		foreach($arr as $index => $line){
			if(!empty($line['minStay'])){
				echo '最低截止'.date('D',strtotime('+'.$line['minStay'], $line['allowDateStart']));
			}
			if(!empty($line['allowDateStart'])){
				echo '  allowDateStart'.date('Y-m-d',strtotime($line['allowDateStart']));
			}
				echo ' #'.$index.'<br>';
		}
	}
	private function noFare($fileName){
        // 防止返回没结果

        if( !opendir($fileName) ) return;
		$f_page = parent::initFile($fileName, 0, 1);
		preg_match_all('/NO\sFARE\sFOR\sTHIS\sMARKET/',end($f_page), $str);
		if(empty($str[0])){ 
			return false; 
		}else{
			return ture;
		}
	}
    public function changePrice(){
        $f_page = parent::initFile($this->tmp, 0, 1);
        preg_match('/1NUC=(.*)CNY/', $f_page[1], $str);
        $rate = floatval($str[1]);
        return $rate;
    }
}
?>