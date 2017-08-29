<?php
use Eterm\Eterm;
class Xfsd extends Eterm{
	private $arr = array();      // 储存数组信息
    private $org_arr = array();  // 原始数组信息
	private $date;
    private $startKey;           // 开始匹配的起始位置
    private $firstPage = '';

    public function analysis($switch){

    	// 解析文件，并返回解析后的结果
    	if(empty($switch)){
	    	$switch = array(1,2,3); // 解析参数：1.格式化 
    	}

        $this->getFirstPage();

    	foreach ($switch as $flags) {
    		switch ($flags) {
    			case 1:
    				// 获得全部页数
 					parent::getAllPage($this->tmp, 'xs/fspn');
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
    	// parent::p($this->tmp);

        $result = $this->arr;
        $this->arr = array();
        
		return $result;
    } 

    // 获取第一页
    public function getFirstPage(){
        // if( $this->firstPage == '' ) // 公用一个tmp变量会导致多地点查询无法正常返回第一页
            $this->firstPage = $this->tmp;
        return $this->firstPage;
    }

    public function readSource(){
        // 获得全部页数
        $this->getAllPage($this->tmp, 'xs/fspn');
        return $this->source;
    }

	private function inputSeat($pageline){
		$pos = intval(substr($pageline, 0, 3)) > 99 ? 3 : 2;

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
	    		if( !$fkey && preg_match_all("/\d{2}\s\w/",substr($pageline,0, 10),$arr)) {
	    			$fkey = $key-1; // 开始匹配舱位的位置，通常是12
                    $this->startKey = $pageArr[$fkey];
	    		}
	    		if(!empty($fkey) && $key > $fkey ){
					if(isset($pageArr[$key+1]) && substr($pageArr[$key+1], 0,4) == '    ' ){  // 带有星期
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
				if(isset($pageArr[$key+1]) && substr($pageArr[$key+1], 0,4) == '    ' ){  // 带有星期
					$pageline = $pageline.substr($pageArr[$key+1], 1);
					$this->inputSeat($pageline);
				}else if(substr($pageArr[$key], 0,4) != '    '){
					$this->inputSeat($pageline);
				}
				
			}
		}
	}

	private function getAllSeat($dataFrom){
		// $data = file_get_contents($dataFrom);
        $data = $dataFrom;
        
    	preg_match_all("/\[CDATA\[(.*?)\]\]/is", $data, $newFile);

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
        $start       = $str[1];
        $end         = $str[2];
        $direction   = $str[3];
        foreach($arr as $key => $dataline){
            $index   = substr($dataline, 0, 3);                  // 序号
            $pos     = 3;
            if(intval($index) > 99){
                $pos = 4;
            }
            $fare    = preg_replace('/\s*/', "", substr($dataline, $pos, 8));                // fare 
            $special = substr($dataline, $pos+9,1);            // 特殊规则
            preg_match_all("/ADVP\s*([0-9]{1,2}D)/",$dataline, $advp);
            $ADVPDay = empty($advp[0])?'':$advp[1][0];             // ADVP 
            // 匹配金额 13~21(9) 单程 +3 24~32(9)
            // 原正则："/\/?\s+([0-9]*\.?[0-9]{0,2})\s*\/[A-Z]{1}/is"
            preg_match("/\s*([0-9]*\.?[0-9]{0,2})/is", substr($dataline, $pos+10, 9), $singleLineFeeArea);
            preg_match("/\s*([0-9]*\.?[0-9]{0,2})/is", substr($dataline, $pos+20, 9), $backLineFeeArea);
            
            if(isset($singleLineFeeArea[1]) && $singleLineFeeArea[1] != ''){
                 $singleLineFee = $singleLineFeeArea[1];
                 $backLineFee   = '';
            }
            // 矫正单程运价区域填充其他信息
            if(isset($backLineFeeArea[1]) && $backLineFeeArea[1] != ''){
                 $singleLineFee = '';
                 $backLineFee   = $backLineFeeArea[1];    
            }
     
            $seat            = substr($dataline, $pos+30, 1);   // 舱位
            $minStay         = substr($dataline, $pos+32, 3);   // 最低滞留时间
            $maxStay         = substr($dataline, $pos+36, 3);   // 最长滞留时间
            $allowDateStart  = substr($dataline, $pos+40, 5);   // 适用截止起始日期
            $allowDateEnd    = substr($dataline, $pos+46, 5);     // 适用截止结束日期
            $reTicket        = substr($dataline, $pos+52, 5);         // 退票规则
            preg_match_all('/[D]\s\d+/', substr($dataline, $pos+58), $arrWeek);
            $allowWeek       = empty($arrWeek[0])? '1234567':substr($arrWeek[0][0],2);            // 可用周期
            // 回填数据
            $this->arr[$key] = array(
                'index'=>$index, 
                'fare'=>$fare, 
                'special'=>$special=='*'?'YES':'NO',
                // 'ADVP'=>$ADVP ? $ADVP :'', 
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

    // 汇率
    public function changePrice(){
        $f_page = parent::initFile($this->tmp, 0, 1);
        preg_match('/1NUC=(.*)CNY/', $f_page[1], $str);
        $rate = floatval($str[1]);
        return $rate;
    }

}