<?php
use Eterm\Eterm;
/**
 * 2017-11-8
 * SK 查询航班时刻
 * SK:/{$DEP}{$ARR}/{$AIR} 
 */
class Sk extends Eterm{
	private $config = array();

	// 设置
	public function set($config = array()){
		if(empty($config)) {
			\BYS\Report::error('输入数据为空！');
			return;
		}
		
		if(!isset($config['start']) || !isset($config['aircompany'])){
			\BYS\Report::error('请输入正确数据！');
			return;
		}

		// 保存
		$this->config = $config;

		// 发送请求
		$command = "SK:/{$config['start']}{$config['end']}/{$config['aircompany']}";
		$this->command($command);

		return $this;
	}

	public function isRouting($str){
		if(preg_match("/\d[\-\+]?\s+\*?[a-zA-Z0-9]+/", substr($str,0,10))){
			return true;
		}
		return false;
	}

	// 将一个行程的数组，转化成数组格式
	public function parseData($array = array()){
		if(empty($array)) return;

		$result = array();
		foreach ($array as $key => $value) {
			$result[] = array(
				'flight'    => substr($value, 4, 5),
				'start'     => substr($value, 12, 3) === '   ' ? substr($array[$key-1], 15, 3) : substr($value, 12, 3),
				'aircompany'=> $this->config['aircompany'],
				'end'       => substr($value, 15, 3),
				'startTime' => substr($value, 19, 4),
				'endTime'   => rtrim(substr($value, 26, 6)),
				'flightType'=> substr($value, 33, 3),
				'haveStay'  => substr($value, 37, 1), 
				'allowWeek' => rtrim(substr($value, 46, 4)), 
				'startDate' => ltrim(substr($value, 51, 5)) === 'DS#'? '' : ltrim(substr($value, 51, 5)),
				'endDate'   => ltrim(substr($value, 56, 5)) === 'DS#'? '' : ltrim(substr($value, 56, 5)),
				'other'     => substr($value, 62),
			);
		}
		return $result;
	}

	// 解析返回结果
	public function parseDetail($config = array()){
		// 保存
		if(!empty($config)){
			$this->config = $config;
		}

		$resArray = parent::fromToArray($this->tmp, 1, 1);
		$result   = array();
		$srcArray = array();
		foreach ($resArray as $key => $value) {
			// 新 Routing 的开始
			if($this->isRouting($value)){
				// 执行处理
				$paresDate = $this->parseData($srcArray);
				if($paresDate)
					$result[] = $paresDate;
				$srcArray = array(); // 清空
				$srcArray[] = $value;
			}else{
				$srcArray[] = $value;
			}

			// 最后一个 
			if($key === count($resArray)-1){
				// 执行处理
				$paresDate = $this->parseData($srcArray);
				if($paresDate)
					$result[] = $paresDate;
			}
		}
		return $result;
	}
}