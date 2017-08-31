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
		protected $tmp;           // 临时保存读取的服务器返回结果
		protected $source;        // 保存即将存入数据库的数据
		public    $fileTime;      // 返回当前时间

		function __construct( $name, $password, $resource){
			$this->name     = $name;
			$this->password = $password;
			$this->resource = $resource;
		}		
		
		public function command($command, $type='w', $p=false, $add = ''){   
			// 向eterm输入命令，并存放在tmp中 ,$p 是否开启打印

			// 初始化
			$this->command = $command;
			$add = $this->command.$this->resource.$add;

			// filesize 返回没正确结果时也重新读取
			// if(!file_exists($this->tmp) || !filesize($this->tmp)>150 ){
		 		$requestURL='http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-'.$this->resource.'&COMMAND='.urlencode($command);
		 	 	$file = file_get_contents($requestURL);
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
	  protected function getAllPage($dataFrom, $command){
			// 获取除了第一页的其他页数据
	    $f = $this->initFile($dataFrom, 0, 1);
			preg_match_all('/PAGE[\s]*(\w+)\/(\w+)/', end($f), $str); // 获取页码

			$pageTotal = intval($str[2][0]);   // 总页码
	    $pageCur   = intval($str[1][0]);   // 当前页码

    	while($pageCur < $pageTotal){
    		$this->addCommand($command ,'a'); // 回填tmp文件
    		$pageCur++;
    	}
		}

	  public function mixCommand($commandArr, $type, $p=false){
	    	// 多条命令发送，数组包含该信息是否回填至缓存中
	  	  $commandURL = '';
	  	  $commandTXT = '';
		    foreach($commandArr as $command => $addFlag){
					$commandURL .= '&COMMAND='.urlencode($command);
					$commandTXT .= $command;
				}

				$this->command = $commandTXT;

				// 以fare和航空公司区分政策文件
				preg_match_all('/\/#[\w]?\*?(\w+)\/\/\//',$this->command, $str);
				$fare = $str[1][0];
				if(!empty($fare)){
					preg_match_all('/\/(\w{2})\//',  $this->command, $str2);
					preg_match_all('/fsn1\/\/(\d*)/',$this->command, $str3);
					$index      = $str3[1][0];
					$aircompany = $str2[1][0];
				}
				
				if(!file_exists($this->tmp) || !filesize($this->tmp)>0){
			 		$requestURL ='http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-BJS248'.$commandURL;
			 	 	$file       = file_get_contents($requestURL);
				 	$this -> saveStr($file, $type);
				}
	  }

	  public function setTime($fileName){
	    return time();
	  }

	  public function addCommand($command, $type){
	    // 追加命令，能重叠发送命令至eterm 并回填至同一个文件
	    $requestURL = 'http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-BJS248&COMMAND='.urlencode($command);
		 	$file       = file_get_contents($requestURL);
			$this->saveStr($file, $type);
	  }


		protected function initFile($dataFrom, $rangeStart = 0, $rangeEnd = 0){
			// 格式化成数组，并截取rangeStart至总长度-rangeEnd的长度
	    $file    = $dataFrom;
			$arr_tmp = array();
			preg_match_all("/\[CDATA\[(.*?)\]\]/is", $file, $newFile);

			foreach ($newFile[1] as $pageNum => $Page) {
    		$pageArr = explode("\r",$Page);
    		$valDate = array_slice($pageArr, $rangeStart, count($pageArr)-$rangeEnd); 
  			$arr_tmp = array_merge($arr_tmp, $valDate);
    	}
    	return $arr_tmp;
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