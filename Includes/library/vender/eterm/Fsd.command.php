<?php
use Eterm\Eterm;
class Fsd extends Eterm{
	private $arr = array();      // 储存数组信息

	// 分配
	public function fare($switch, $date, $command){
		// 解析政策文件，并返回解析后的结果
		$this->date = $date;
	    $this->command = $command;

		if(empty($switch)){
	  	$switch = array(0,1,2,3); 
		}

		foreach ($switch as $flags => $value) {
			switch ($flags) {
				case 0:
					// 跳转某项目, 回填tmp文件，回填时判断能否
					$this->fsn1($switch[$flags]);
					continue;
				case 1:
					// 获得全部页数
					parent::getAllPage($this->tmp, 'xs/fspn');
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
	        // 无数据
	        return false;
	    }
	}

	private function fsn1($index){
    // 文件读取xs/fs后，如果文件小于10K执行fsn1，查看某项
    $command_1 = $this->command;
		if( empty($index) ){
			$command_2 = 'xs/fsn1'; 
		}else{
			$command_2 ='xs/fsn1//'.$index; 
		}
    // $commandArr = array($command_1=>0, $command_2=>1);
    $commandArr = array($command_1, $command_2);
    parent::mixCommand($commandArr,'w');	
    // var_dump($this->tmp);
  }

  private function f_fliter_useless($fileName){
  	$arr = parent::initFile($fileName, 0, 1);
    $arr_tmp = $arr;

    $fliter_str_1 = '/PAGE[\s]*(\w+)\/(\w+)/';               // 匹配页所在行
    $fliter_str_2 = '/\<\</';                                // 匹配<<
    $fliter_str_3 = "/FSN.*{$this->date['startDate']}/i";    // 匹配分页前缀
    $fliter_str_4 = '/\*+\s+END\s+\*+/';                     // 去除**end**结尾
    $fliter_str_5 = "/(\d){2}\.[A-Z]+([-|\/|\s]+[A-Z]+)?/i"; // 匹配序号
    $fliter_str_6 = "/\d+\s+{$this->date['fare']}\s+\/?/i";  // 匹配fare

  	foreach ($arr as $key => $line) {
      if(!isset($arr_tmp[$key])) continue;

  		if(preg_match($fliter_str_1, $line)){
  			unset($arr_tmp[$key]);
  		}elseif(preg_match($fliter_str_2, $line)){
  			$arr_tmp[$key] = preg_replace($fliter_str_2, '  ', $line);
  		}elseif(preg_match($fliter_str_6, $line)){
  			unset($arr_tmp[$key]);
  		}elseif(preg_match($fliter_str_3, $line)){
  			if(preg_match_all('/D\s+\d+/', $arr_tmp[$key+3])){  // 如果带有D 123类型
  				unset($arr_tmp[$key+3]);
  			}
  			unset($arr_tmp[$key]);
  			unset($arr_tmp[$key+1]);
  		}elseif(preg_match($fliter_str_4, $line)){
  			unset($arr_tmp[$key]);
  		}
  	}
    // 重置数组，查找序号，并分块
		$arr_tmp   = array_values($arr_tmp);
    $length    = count($arr_tmp);
    $arr_block = array();
    $str       = '';
  	foreach($arr_tmp as $key => $line){
  		if(preg_match_all($fliter_str_5, $line, $a)){
  			$start = $key; 
  			$arr_block["{$a[0][0]}"] = array();
  			for($i = $key+1; $i< $length; $i++){
  				if(preg_match_all($fliter_str_5, $arr_tmp[$i], $b)){  // 结尾
  					break;
  				}
          $str .= "\r".$arr_tmp[$i];
          $arr_block["{$a[0][0]}"] = $str;
  			}
         $str = '';
  			continue;
  		}
  	}
  	$this->arr = $arr_block;
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


}