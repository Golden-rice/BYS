<?php
use Eterm\Eterm;
class Xfsd extends Eterm{
	private $arr         = array();  // 储存数组信息
    private $org_arr     = array();  // 原始数组信息
	private $date;
    private $startKey    = '';       // 开始匹配的起始位置
    private $firstPage   = '';
    private $keyFeature  = array();  // xfsd中含有地点区域标识
    private $featureWord = '';       // 用于匹配keyFeature的字符

    function __construct( $query = array()){
        parent::__construct('','','',$query);
    }

    public function toArray(){
        return $this->displayV2(explode("\r", $this->tmp));
    }

    public function analysis($switch){

    	// 解析文件，并返回解析后的结果
    	if(empty($switch)){
	    	$switch = array(1,2,3); // 解析参数：1.格式化 
    	}

        $this->getFirstPage();

    	foreach ($switch as $flagKey=> $flags) {
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
            unset($switch[$flagKey]);
    	}

    	
        // 特征组
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


    // 用下一行做标记
    private function setKeyFeature($str = ''){
        $str = $this->clear($str);
        // 当匹配到标识时
        if($str === '' ) return;
        // key 未所在行，保存
        if(preg_match("/{$this->featureWord}/", $str)  && !in_array($str, $this->keyFeature))
            $this->keyFeature[] = $str;
    }

    private function setFeatureWord($str = ''){
        $this->featureWord = preg_replace('/([\/|\\|*|.|+])/', "\\\\$1", substr($this->clear($str), 0, 18));
    }

	private function getSeat($page, $isF){
		// 传递该页page所含舱位，判断是否为第一页
		$pageArr = explode("\r",$page);
		$pageArr = array_slice($pageArr, 0, count($pageArr)-2);
		$fkey    = -1;

        if($isF){ // 如果是第一页
	    	foreach ($pageArr as $key => $pageline) {
	    		if( $fkey < 0 && preg_match_all("/\d{2}\s\w/",substr($pageline,0, 10),$arr)) {
	    			$fkey = $key; // 开始匹配舱位的位置，通常是12
                    $this->startKey = $pageArr[$fkey-1];
                    $this->setFeatureWord($this->startKey);
        		    break;
                }
            }
        }else{
            $fkey = 0 ;
        }
        // 生成有效信息组合的数组（除第一页的特征）
        if( $fkey >= 0)
            $pageArr = array_slice($pageArr, $fkey, count($pageArr));
        else
            return;
        
            
        foreach ($pageArr as $key => $pageline) {
            // 检验是否有特征
            $this->setKeyFeature($pageline);

            // 匹配正确数据
			if(isset($pageArr[$key+1]) && substr($pageArr[$key+1], 0,4) == '    ' ){  // 带有星期
				$pageline = $pageline.substr($pageArr[$key+1], 1);
				$this->inputSeat($pageline);
            }else if(substr($pageArr[$key], 0,4) != '    '){
                $this->inputSeat($pageline);
            }
    		
        }
	}

	private function getAllSeat($dataFrom){
		// $data = file_get_contents($dataFrom);
        $data = $dataFrom;
        
    	preg_match_all("/\[CDATA\[(.*?)\]\]/is", $data, $newFile);

		foreach ($newFile[1] as $pageNum => $Page) {
    	    $pageNum == 0? $this->getSeat($Page, 1): $this->getSeat($Page, 0);
    	} 
        $this->org_arr = $this->arr;   
	}

    private function display($arr){
        // update by xiaojia
        // 无数据时

        if($this->startKey == '') return;
        
        // 初始化特征值
        preg_match('/\s(\w{3})(\w{3})\/(\w{2})/',$this->startKey, $str);
        $start       = $str[1];
        $end         = $str[2];
        $direction   = $str[3];
        $curFeatureKey  = 0; // 当前特征序号

        foreach($arr as $key => $dataline){
            $index   = substr($dataline, 0, 3);                  // 序号

            // 是否放入更改特征
            if(!empty($this->keyFeature) && (int)$index >= (int)$curFeatureKey) {
                foreach ($this->keyFeature as $featureKey => $feature) {
                    if ( (int)$index >= (int)$featureKey ){
                        $curFeatureKey = (int)$featureKey;
                        preg_match('/\s(\w{3})(\w{3})\/(\w{2})/',$feature, $str);
                        $start       = $str[1];
                        $end         = $str[2];
                        $direction   = $str[3];
                        unset($this->keyFeature[$featureKey]);
                    }
                }
            }
           
            $pos =  (int)$index > 99 ? 4: 3;

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
                'reTicket'=>rtrim($reTicket), 
                'allowWeek'=>$allowWeek,
                'start' => $start,
                'end' => $end,
                'direction' => $direction
            );
            if(!empty($this->query)){
                $this->arr[$key]['from']       = $this->query['code']==''?'':$this->query['code'];
                $this->arr[$key]['aircompany'] = $this->query['aircompany'];
                $this->arr[$key]['startDate']  = $this->query['startDate'];
                $this->arr[$key]['command']    = $this->command;
                $this->arr[$key]['other']      = $this->query['other'];
            }

        }
    }

    // --------------------------- 新方法 ---------------------------
    // 分析string 返回特征数组： 出发 到达 方向
    private function parseFeature($str){
        $str = $this->clear($str);
        if(!empty($this->featureWord) && preg_match("/{$this->featureWord}/", $str)){
            preg_match('/\s(\w{3})(\w{3})\/(\w{2})\//', $str, $match);
            return array(
                'start'     => $match[1],
                'end'       => $match[2],
                'direction' => $match[3]
            );
        }
        return false;
    }

    private function mkArrayData($dataline = ''){
        if(empty($dataline)){
            echo '不能为空';
            return;
        } 

        $index   = substr($dataline, 0, 3);                  // 序号
        $pos     =  (int)$index > 99 ? 4: 3;
        $fare    = preg_replace('/\s*/', "", substr($dataline, $pos, 8));                // fare 
        $special = substr($dataline, $pos+9,1);            // 特殊规则
        preg_match_all("/ADVP\s*([0-9]{1,2}D)/",$dataline, $advp);
        $ADVPDay = empty($advp[0])?'':$advp[1][0];             // ADVP 
        // 匹配金额 13~21(9) 单程 +3 24~32(9)
        // 原正则："/\/?\s+([0-9]*\.?[0-9]{0,2})\s*\/[A-Z]{1}/is"
        preg_match("/\s*([0-9]*\.?[0-9]{0,2})/is", substr($dataline, $pos+10, 9), $singleLineFeeArea);
        preg_match("/\s*([0-9]*\.?[0-9]{0,2})/is", substr($dataline, $pos+20, 9), $backLineFeeArea);

        $singleLineFee = '';
        $backLineFee  = '';

        if(isset($singleLineFeeArea[1]) && $singleLineFeeArea[1] != '')
             $singleLineFee = $singleLineFeeArea[1];
        
        // 矫正单程运价区域填充其他信息
        if(isset($backLineFeeArea[1]) && $backLineFeeArea[1] != '')
             $backLineFee   = $backLineFeeArea[1];    
 
        $seat            = substr($dataline, $pos+30, 1);   // 舱位
        $minStay         = substr($dataline, $pos+32, 3);   // 最低滞留时间
        $maxStay         = substr($dataline, $pos+36, 3);   // 最长滞留时间
        $allowDateStart  = substr($dataline, $pos+40, 5);   // 适用截止起始日期
        $allowDateEnd    = substr($dataline, $pos+46, 5);     // 适用截止结束日期
        $reTicket        = substr($dataline, $pos+52, 5);         // 退票规则
        preg_match_all('/[D]\s\d+/', substr($dataline, $pos+58), $arrWeek);
        $allowWeek       = empty($arrWeek[0])? '1234567':substr($arrWeek[0][0],2);            // 可用周期
        // 回填数据
        return array(
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
            'reTicket'=>rtrim($reTicket), 
            'allowWeek'=>$allowWeek,
            // 'start' => $start,
            // 'end' => $end,
            // 'direction' => $direction
        );
    }

    private function isNew($str){
        $str = $this->clear($str);
        if(!empty($str) && preg_match("/>XSFSD(\w{3})(\w{3})\/(\w{2})\//", $str, $match)){
            return array(
                'start'     => $match[1],
                'end'       => $match[2],
                'aircompany'=> $match[3]
            );
        }
        return false;
    }

    private function isContinueLine($str){
        if(substr($this->clear($str), 0,4) == '    ' )
            return true;
        return false;
    }

    private function clear($str){
        return preg_replace("/[\r|\n|\t]+/", "", $str);
    }

    private function displayV2($arr){
        // 初始化
        $result = array();

        // 预设
        $this->setFeatureWord($arr[1]);
        $totel = 0;
        foreach ($arr as $key => $value) {
            // 清除多余格式
            $value = $this->clear($value);

            // 是否为新结果
            $isNew = $this->isNew($value);
            if($isNew){
                $result[$isNew['end']] = array();
                continue;
            }

            // 是否更改特征
            $feature = $this->parseFeature($value);
            if($feature){
                $start     = $feature['start'];
                $end       = $feature['end'];
                $direction = $feature['direction'];
                continue;
            }

            // 是否下一行为该行的数据
            if(isset($arr[$key+1]) && $this->isContinueLine($arr[$key+1])){
                $value .= $this->clear($arr[$key+1]);
            }

            // 是否为上一行的数据
            if($this->isContinueLine($value)){
                continue;
            }

            $insertData = $this->mkArrayData($value);

            // 扩充参数
            $insertData['start']        = $start;
            $insertData['end']          = $end;
            $insertData['direction']    = $direction;

            $result[$end][]             = $insertData;
            $result[$end]['from']       = '';
            $result[$end]['aircompany'] = '';
            $result[$end]['startDate']  = '';
            $result[$end]['command']    = '';
            $result[$end]['other']      = '';
            $result[$end]['length']     = ++$totel; // 必填
        }
        return $result;
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

    // 返回汇率
    public function changePrice(){
        $f_page = parent::initFile($this->tmp, 0, 1);
        if(isset($f_page[1]) && preg_match('/1NUC=(.*)CNY/', $f_page[1], $str))
            $rate = floatval($str[1]);
        else
            $rate = 0;
        return $rate;
    }

}