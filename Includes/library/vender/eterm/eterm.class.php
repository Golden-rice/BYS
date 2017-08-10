<?php
namespace Eterm;
	set_time_limit(0); // 无时间限制
	class Eterm{
		public $name;
		public $password;
		public $resource;
		public $command;
		private $runDir;
		protected $config;
		protected $tmp;
		public $fileTime;
		protected $commandQueue = array(); // 2.0 命令队列

		function __construct($name, $password, $resource, $runDir=""){
			$this->name = $name;
			$this->password = $password;
			$this->resource = $resource;
			$this->runDir = $runDir;
			// 2.0 配置
			$this->config = array(
				"print"=>false,
				"save"=>true,
				"queue"=>true
			);
		}		
		
		public function command($command, $type, $p=false, $add = ""){   
			// 向eterm输入命令，并存放在tmp中 ,$p 是否开启打印

			// 初始化
			$this->command = $command;

			// 打印
			if($p){ 
				$txt = preg_replace(array('/</','/>/'),array('&lt;','&gt;'), $command);
				$this->p( '->'.$txt); 
			}

			$add = $this->command.$this->resource.$add;
			$this->setRuntimeFileName( $this->runDir, $add );

			// filesize 返回没正确结果时也重新读取
			if(!file_exists($this->tmp) || !filesize($this->tmp)>150 ){
		 		$requestURL='http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-'.$this->resource.'&COMMAND='.urlencode($command);
		 	 	$file = file_get_contents($requestURL);
		 	 	
		 	 	// if($this->runDir !== ""){
		 	 	// 	$this->download($file, $type, $add);
		 	 	// }
		 	 	$this->saveStr($file, $type);
			}
	  }

	  public function removeRuntime($command, $add = ""){
	  	// 初始化
			$this->command = $command;

			$add = $this->command.$this->resource.$add;
			$this->setRuntimeFileName( $this->runDir, $add );

			$this->delete($this->tmp);

	  }

	  private function delete($filename){
	  	if(!is_dir($this->runDir)){ return; }

	  	if(file_exists($filename)){
	  		unlink($filename);
	  	}
	  }

	  private function download($file, $type){
	  	// 保存至文件中

	    $tmpfile = fopen($this->tmp, $type);
			fwrite($tmpfile, $file);
			fclose($tmpfile);
	  }

	  private function saveStr($file, $type){
    	if($type == 'w'){
	    	$this->tmp = $file;
    	}else if($type == 'a' ){
    		$this->tmp .= $file;
    	}
    	$this->fileTime = $this->setTime($this->tmp);
	  }

	  public function mixCommand($commandArr, $type, $p=false){
	    	// 多条命令发送，数组包含该信息是否回填至缓存中
		    foreach($commandArr as $command => $addFlag){
					$commandURL .= '&COMMAND='.urlencode($command);
					$commandTXT .= $command;
				}
				if($p){ 
					$txt = preg_replace(array('/</','/>/'),array('&lt;','&gt;'),key($commandArr));
					$this->p( '->'.$txt); 
				}

				$this->command = $commandTXT;


				// 以fare和航空公司区分政策文件
				preg_match_all('/\/#[\w]?\*?(\w+)\/\/\//',$this->command, $str);
				$fare = $str[1][0];
				if(!empty($fare)){
					preg_match_all('/\/(\w{2})\//',$this->command, $str2);
					preg_match_all('/fsn1\/\/(\d*)/',$this->command, $str3);
					$index = $str3[1][0];
					$aircompany = $str2[1][0];
					$this->setRuntimeFileName($this->runDir, $fare.$aircompany.$index);
				}else{
					$this->setRuntimeFileName($this->runDir, $this->command);
				}
				
				if(!file_exists($this->tmp) || !filesize($this->tmp)>0){
			 		$requestURL='http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-BJS248'.$commandURL;
			 	 	$file = file_get_contents($requestURL);

				 	$this->download($file, $type);
				 	// $this->saveStr($file, $type);
				}
	  }

	  public function setTime($fileName){
	    	if(file_exists($fileName)){
		    	return filemtime($fileName);
	    	}else{
	    		return time();
	    	}
	  }

	  public function addCommand($command, $type){
	    // 追加命令，能重叠发送命令至eterm 并回填至同一个文件

	    $requestURL='http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-BJS248&COMMAND='.urlencode($command);
		 	$file = file_get_contents($requestURL);
			// $this->download($file, $type);
			$this->saveStr($file, $type);

	  }

	  private function setRuntimeFileName($dir, $add){
			// 将文件夹地址，添加信息，文件名组合成新的文件地址
			// $addtext = urlencode(convert_uuencode ($add)); // 混淆命名
			$addtext = preg_replace("/[\/|\.|\*|\#|\<|\>|\r|\n|\t]/", "_", $add);
			$this->tmp = $dir.'_'.$addtext.'.xml';
			$this->fileTime = $this->setTime($this->tmp);

			return $this->tmp;
		}

		protected function initFile($fileName, $rangeStart = 0, $rangeEnd = 0){
			// 格式化成数组，并截取rangeStart至总长度-rangeEnd的长度
	    // $file = file_get_contents($fileName); // 文件下载方式
	    $file = $fileName;
			$arr_tmp = array();
			preg_match_all("/\[CDATA\[(.*?)\]\]/is", $file, $newFile);
			foreach ($newFile[1] as $pageNum => $Page) {
	    		$pageArr = explode("\r",$Page);
	    		$valDate = array_slice($pageArr, $rangeStart, count($pageArr)-$rangeEnd); 
    			$arr_tmp = array_merge($arr_tmp, $valDate);
	    	}
	    	return $arr_tmp;
		} 

		public function readFile($rangeStart = 0, $rangeEnd = 0){
			$fileName = $this->tmp;
			$a = $this->initFile($fileName, $rangeStart, $rangeEnd);
			return $a;
			// $this->p($a);
		}

		protected function p($name){
			// 打印
	 		if($name){
		 		echo "<pre>";
		 		print_r($name);
		 		echo '</pre>';
			}
		}	 

		// 2.0 接口
		public function query($command){
			if(!$command) return;

			if(is_string($command)){

				$this->command = $command;

				if($this->config["print"]){
					$txt = preg_replace(array('/</','/>/'),array('&lt;','&gt;'), $command);
					$this->p( '->'.$txt); // 1.0 p 打印命令
				}


				if($this->config["queue"]){
					$this->queue($this->command);
				}else{
					$this->req($this->command);
				}

			}else if(is_array($command)){
				$this->command = $command; // 数组类型

				if($this->config["print"]){
					foreach ($command as $key => $value) {
						$txt = preg_replace(array('/</','/>/'),array('&lt;','&gt;'), $key);
						$this->p( '->'.$txt); // 1.0 p 打印命令
					}
				}

				// req?
			}

		}

		private function queue($command){
			// push
			array_push($this->commandQueue, $command); 
		}

		private function req($command){

			$fileName = $this->setRuntimeFileName( $this->runDir, $command.$this->resource ); // 1.0 setRuntimeFileName 设置文件名

			if(!file_exists($fileName) || !filesize($fileName)>150 ){
		 		$file = file_get_contents('http://eterm.cctba.com:8350/COMMAND?USER='.$this->name.'&PASSWORD='.$this->password.'&RESOURCEETERM-SHARE-'.$this->resource.'&COMMAND='.urlencode($command));

		 		var_dump($file);

		 	 	if($this->config["save"] && $this->runDir !== ""){
		 	 		$this->download($file, $type, $add); // 1.0 download 保存文件
		 	 	}else{
			 	 	$this->saveStr($file, $type); // 1.0 saveStr 储存在 $this->tmp 这个变量中
		 	 	}
			}
		}

		public function putFile($fileName, $rangeStart = 0, $rangeEnd = 0){
			// 格式化成数组，并截取rangeStart至总长度-rangeEnd的长度
	    $file = file_get_contents($fileName); // 文件下载方式
	    // $file = $fileName;
			$arr_tmp = array();
			preg_match_all("/\[CDATA\[(.*?)\]\]/is", $file, $newFile);
			foreach ($newFile[1] as $pageNum => $Page) {
	    		$pageArr = explode("\r",$Page);
	    		$valDate = array_slice($pageArr, $rangeStart, count($pageArr)-$rangeEnd); 
    			$arr_tmp = array_merge($arr_tmp, $valDate);
	    	}
	    	return $arr_tmp;
		}


		function __destruct(){
			if($this->config["queue"] && $this->commandQueue){
				$queue_config = array(
					"sleep_time" => 10,
					"freq" => 50
				);

		  	$while = intval(count($this->commandQueue)/$queue_config["freq"]);
		  	$last  = count($this->commandQueue)%$queue_config["freq"];  // 剩余

		  	var_dump($this->commandQueue);

			  for($j = 0; $j< $while; $j++){
				  if(count($this->commandQueue)>$queue_config["freq"]){
					  for($i = 0; $i<$queue_config["freq"]; $i++){
					  	$this->req(array_shift($this->commandQueue));
					  }
				  }
			  }
			  // last
			  for ($l=0; $l < $last; $l++) { 
			  	$this->req(array_shift($this->commandQueue));
			  }
			}
		}


	}
?>