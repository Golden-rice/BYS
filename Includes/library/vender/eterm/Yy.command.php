<?php
use Eterm\Eterm;
class Yy extends Eterm{
	// 设置参数
	private $config = array();
	
	// 设置
	public function set($config = array()){
		if(empty($config)) {
			\BYS\Report::error('输入数据为空！');
			return;
		}
		
		// 必填项
		if(!isset($config['start']) ){
			\BYS\Report::error('请输入正确数据！');
			return;
		}

		// 发送请求
		$command  = "YY/{$config['start']}";

		if(isset($config['end']))
			$command  .= $config['end'];
		
		if(isset($config['aircompany']))
			$command  .= "/{$config['aircompany']}";

		// 保存
		$this->config  = $config;
		$this->command = $command;

		return $this;
	}

	public function run(){
		$this->command($this->command);
		return $this;
	}

	// 一直读到 THE END ，存到 this->tmp中
	public function getToEnd(){
		$src = $this->tmp;
		if(!preg_match("/THE\sEND|NO\sROUTING/", $src)){
			do{
				$addResult = parent::addCommand('PN','a');
			}while(!preg_match("/THE\sEND/", $addResult));
		}
		return $this;
	}

	// 解析字符串，返回数组
	public function parseData($string = '', $aircompany){
		if(empty($string)) return;

		// 是不是共享航班
		if(substr($string, 0, 1) === "*" ){
			$isCommon = 1;
			$string = substr($string, 1);
		}else{
			$isCommon = 0;
		}
		$result = array();
		$array  = explode(" ", trim(preg_replace('/\+|\-/', "", $string)));
		if(!isset($this->config['end'])){
			foreach ($array as $key => $value) {
				$result[] = array(
					'start'     => $this->config['start'],
					'end'       => $value,
					'aircompany'=> $aircompany,
					'isCommon'  => $isCommon
				);
			}
		}else{
			foreach ($array as $key => $value) {
				$result[] = array(
					'start'     => substr($value, 0,3),
					'end'       => substr($value, 3,3),
					'aircompany'=> $aircompany,
					'isCommon'  => $isCommon
				);
			}
		}
		return $result;
	}


	// 解析返回结果
	public function parseDetail($config = array()){

		// 保存
		if(!empty($config)){
			$this->config = $config;
		}
		$resArray   = parent::fromToArray($this->tmp, 1, 1);
		if(empty($resArray)) return array('result' =>array(), 'command'=>$this->command);

		// 去除最后的结束标识
		if(preg_match("/THE\sEND/", $resArray[count($resArray)-1])){
			unset($resArray[count($resArray)-1]);
		}

		$srcString  = ''; // 出发到达 组成的字符串
		$isCommon   = ''; // 每个航段的航空公司是否是共享航班
		$aircompany = ''; // 每个航段的航空公司
		$result     = array();
		
		foreach ($resArray as $key => $value) {
			// 第一次
			if($key === 0){
				if(preg_match("/^(\*|\s)?([0-9A-Z]{2})\*?\s/", $value, $match)){
					$isCommon   = trim($match[1]);
					$aircompany = $match[2];
				}
			}
			if(preg_match("/^(\*|\s)?([0-9A-Z]{2})\*?\s/", $value, $match)){

					// 提交上一次
				$parseData  = $this->parseData($isCommon.$srcString, $aircompany);
				
				if($parseData)
					$result[] = $parseData;

				// 重置
				if(preg_match("/^(\*|\s)?([0-9A-Z]{2})\*?\s/", $value, $match)){
					$isCommon   = trim($match[1]);
					$aircompany = $match[2];
				}
				$srcString  = '';
			}else{
				$srcString .= preg_replace("/\s{2,}|\n|\r/", " ", $value);
			}

			// 结束
			if($key === count($resArray)-1){
				$parseData  = $this->parseData($isCommon.$srcString, $aircompany);
				if($parseData)
					$result[] = $parseData;
			}
		}
		return array('result' =>$result, 'command'=>$this->command);
	}


}