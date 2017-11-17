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

		// 发送请求
		$command = "SK:P/{$config['start']}{$config['end']}/{$config['aircompany']}"; // P: 按照出发时间排序
		if(isset($config['stay']))
			$command .= "/{$config['stay']}";

		// 保存
		$this->config  = $config;
		$this->command = $command;

		return $this;
	}

	public function run(){
		$this->command($this->command);
		return $this;
	}

	// 是否新的航路
	public function isRouting($str){
		if(preg_match("/\d[\-\+]?\s+\*?[a-zA-Z0-9]+/", substr($str,0,10))){
			return true;
		}
		return false;
	}

	// 读取全部页数
	public function getAll(){
		parent::getPageByDate($this->tmp);
		return $this;
	}

	// 将一个行程的数组，转化成数组格式
	public function parseData($array = array()){
		if(empty($array)) return;

		$result = array();
		foreach ($array as $key => $value) {
			$result[] = array(
				'isCommon'  => substr($value, 3, 1) === '*' ? 1:0,
				'flight'    => substr($value, 4, 6),
				'depart'    => substr($value, 12, 3) === '   ' ? substr($array[$key-1], 15, 3) : substr($value, 12, 3),
				'aircompany'=> $this->config['aircompany'],
				'arrive'    => substr($value, 15, 3),
				'departTime'=> substr($value, 19, 4),
				'arriveTime'=> rtrim(substr($value, 26, 6)),
				'flightType'=> substr($value, 33, 3),
				'haveStay'  => substr($value, 37, 1), 
				'allowWeek' => rtrim(substr($value, 46, 4)), 
				'startDate' => ltrim(substr($value, 51, 5)) === 'DS#'? '' : ltrim(substr($value, 51, 5)),
				'endDate'   => ltrim(substr($value, 56, 5)) === 'DS#'? '' : ltrim(substr($value, 56, 5)),
				'other'     => substr($value, 62),
				'date'      => isset($this->title['date']) ? substr($this->title['date'][1], 0, 5) : strtoupper(date('dM', time()))
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
		
		// 获取 title 保存日期
		parent::paresDetailTitle(substr($this->tmp, 114, 80));

		// SK 的分行出现了错乱
		$resArray = parent::fromToArray($this->tmp, 1, 0);

		if(isset($resArray[0]) && strlen($resArray[0]) > 80){
			// 检测是否需要重新分行，如果第一行字符长度大于80
			$resArray = parent::toArrayByLength($resArray);
		}
		// 去除最后的一个符号
		unset($resArray[count($resArray)-1]);

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