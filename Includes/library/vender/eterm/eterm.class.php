<?php
// +----------------------------------------------------------------------
// | Eterm [ 航信解析 ]
// +----------------------------------------------------------------------
namespace Eterm;
// 无时间限制

set_time_limit(0); 
class Eterm{
		public    $name;          // eterm 配置的用户名
		public    $password;      // eterm 配置的密码
		public    $resource;      // eterm 配置号
		public    $command;       // eterm 查询命令
		public    $query;         // eterm 查询参数
		protected $tmp;           // 临时保存读取的服务器返回结果
		protected $source;        // 保存即将存入数据库的数据
		public    $fileTime;      // 返回当前时间

		function __construct( $name = '', $password = '', $resource = '', $query = array()){
			if(isset($_SESSION['name'])) {
				if(empty($name)){
					$this->name     = $_SESSION['name'];
					$this->password = $_SESSION['password'];
					$this->resource = $_SESSION['resource'];
				}else{
					$this->name     = $name;
					$this->password = $password;
					$this->resource = $resource;
				}
			}else{
				\BYS\Report::error('没设置账号');
			}
			$this->query    = $query;    
		}		
		
		public function command($command, $type='w', $p=false, $add = ''){   
			// 向eterm输入命令，并存放在tmp中 ,$p 是否开启打印

			// 初始化
			$this->command = $command;
			$add = $this->command.$this->resource.$add;

			// filesize 返回没正确结果时也重新读取
			// if(!file_exists($this->tmp) || !filesize($this->tmp)>150 ){
		 		$requestURL='http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-'.$this->resource.'&COMMAND='.urlencode($command);
		 	 	try{
			 	 	$file = file_get_contents($requestURL);
		 	 	}catch(Exception $e){
		 	 		 echo 'Caught exception: ',  $e->getMessage(), "\n";
		 	 		 return;
		 	 	}
			 	$this->saveStr($file, $type);
			// }
	  }


	  private function saveStr($file, $type){
    	if($type == 'w'){
	    	$this->tmp    = $file;
	    	$this->source = $file;

	    	// $this->source = $this->readData($file);
    	}else if($type == 'a' ){
    		$this->tmp    .= $file;
    		$this->source .= $file;

    		// $this->source .= $this->readData($file)."\r";
    	}
    	$this->fileTime = $this->setTime($this->tmp);
	  }
	  // 读 this->tmp
    public function rtTmp(){
      return $this->tmp;
    }
    // 写 this->tmp
    public function wtTmp($str = ''){
        if ($str != '') $this->tmp = $str;
    }
    // 返回 command 
    public function rtCommand(){
    	return $this->command;
    }

    // 返回 title
    public function rtTitle(){
    	if(isset($this->title))
    		return $this->title;
    	return false;
    }

    // 根据 page 页数获得所有页数
	  protected function getAllPage($dataFrom, $command, $type = 'a'){
			// 获取除了第一页的其他页数据
	    $f = $this->initFile($dataFrom, 0, 1);
			preg_match_all('/PAGE[\s]*(\w+)\/(\w+)/', end($f), $str); // 获取页码

			if(!isset($str[2][0])) return;
			$pageTotal = intval($str[2][0]);   // 总页码
	    $pageCur   = intval($str[1][0]);   // 当前页码

    	while($pageCur < $pageTotal){
    		$this->addCommand($command, $type); // 回填tmp文件
    		$pageCur++;
    	}

		}

		// 解析文本的头部
		protected function paresDetailTitle($title){
			// SK 头： 15NOV(WED)/21NOV(TUE) BJSABQ VIA UA/SFO  
			// AV 头： 06OCT(FRI) BJSORD VIA UA
			if(preg_match("/^\s([1-9A-Z\/\(\)]+)+\s(\w+)\sVIA\s([A-Z\/]+)/",$title, $match))
				$result = array(
					'date' => explode('/', $match[1]),
					'routing' => $match[2],
					'other' => $match[3]
				);
			else
				$result = false;

			$this->title = $result;
		}

		// 根据 日期 获得所有页数
		protected function getPageByDate($dataFrom, $command='PN', $type = 'a'){
			// $f = $this->initFile($dataFrom, 0, 1);
			// $title = $this->paresDetailTitle($f[0]);
			$title = $this->paresDetailTitle(substr($dataFrom, 114, 80));

			if($title){
				do{
					$curTmp  = $this->addCommand($command, $type, true);
					$curF    = $this->initFile($curTmp, 0, 1); 
					$curTitle= $this->paresDetailTitle($curF[0]);
					// 如果日期一致，save tmp
					if($curTitle['date'][0] === $title['date'][0])
						$this->saveStr($curTmp, 'a');

				}while($curTitle['date'][0] === $title['date'][0]);
			}
			return $title;
		}

		// 根据长度转换成数组
		public function toArrayByLength($source = array(), $length = 80){
			$change = array();
			foreach ($source as $value) {
				if(strlen($value)>80 && $round = strlen($value) / 80)
					for ($i=0; $i < $round; $i++) { 
						array_push($change, substr($value, $i*80, 80));
					}

			}
			return $change;
		}

	  public function mixCommand($commandArr, $type = 'w', $p=false){
    	// 多条命令发送，数组包含该信息是否回填至缓存中
  	  $commandURL = '';
  	  $commandTXT = '';

	    foreach($commandArr as $command){
				$commandURL .= '&COMMAND='.urlencode($command);
				$commandTXT .= $command;
			}

			$this->command = $commandTXT;

			// 以fare和航空公司区分政策文件
			/*
			preg_match_all('/\/#[\w]?\*?(\w+)\/\/\//',$this->command, $str);
			$fare = $str[1][0];
			if(!empty($fare)){
				preg_match_all('/\/(\w{2})\//',  $this->command, $str2);
				preg_match_all('/fsn1\/\/(\d*)/',$this->command, $str3);
				$index      = $str3[1][0];
				$aircompany = $str2[1][0];
			}
			*/

	 		$requestURL='http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-'.$this->resource.$commandURL;
	 	 	try{
		 	 	$file = file_get_contents($requestURL);
	 	 	}catch(Exception $e){
	 	 		 echo 'Caught exception: ',  $e->getMessage(), "\n";
	 	 		 return;
	 	 	}
		 	$this->saveStr($file, $type);

	  }

	  public function setTime($fileName){
	    return time();
	  }

	  public function addCommand($command, $type = 'a', $return = false){
	    // 追加命令，能重叠发送命令至eterm 并回填至同一个文件
	    $requestURL = 'http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-BJS248&COMMAND='.urlencode($command);
		 	$file       = file_get_contents($requestURL);
		 	
		 	if($return)
				return $file;
			else
				$this->saveStr($file, $type);
	  }

	  // $rangeStart 截取的起始位置， $rangeEnd 截取的结束位置
	  // 0,(1,2,3,)4,5 start = 1, length = 2 => $rangeStart = 1, $rangeEnd = 3
		protected function initFile($dataFrom, $rangeStart = 0, $rangeEnd = 0){
			// 格式化成数组，并截取rangeStart至总长度-rangeEnd的长度
	    $file    = $dataFrom;
			$arr_tmp = array();
			preg_match_all("/\[CDATA\[(.*?)\]\]/is", $file, $newFile);
			foreach ($newFile[1] as $pageNum => $Page) {
    		$pageArr = explode("\r",$Page);
    		$valDate = array_slice($pageArr, $rangeStart, count($pageArr)-$rangeStart-$rangeEnd); 
  			$arr_tmp = array_merge($arr_tmp, $valDate);
    	}
    	return $arr_tmp;
		} 


		protected function fromToArray($dataFrom, $rangeStart = 0, $rangeEnd = 0){
			return $this->initFile($dataFrom, $rangeStart, $rangeEnd);
		}

		protected function readData($file){
			preg_match_all("/\[CDATA\[(.*?)\]\]/is", $file, $newFile);
			return $newFile[1][0]."\r";
		}

		public function readFile($rangeStart = 0, $rangeEnd = 0){
			$fileName = $this->tmp;
			return $this->initFile($fileName, $rangeStart, $rangeEnd);
		}

		protected function p($name){
			// 打印
	 		if($name){
		 		echo "<pre>";
		 		print_r($name);
		 		echo '</pre>';
			}
		}	 


	}
?>