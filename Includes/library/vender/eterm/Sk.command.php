<?php
use Eterm\Eterm;
/**
 * 2017-11-8
 * SK 查询航班时刻
 * SK:/{$DEP}{$ARR}/{$AIR} 
 */
class Sk extends Eterm{
	private $switch = 0;

	// 设置
	public function set($config = array(), $switch = 0){
		if(empty($config)) {
			\BYS\Report::error('输入数据为空！');
			return;
		}
		
		if(!isset($config['start']) || !isset($config['aircompany'])){
			\BYS\Report::error('请输入正确数据！');
			return;
		}

		// 发送请求
		$command = "SK:/{$config['start']}{$config['end']}/{$config['aircompany']}";
		$this->command($command);

		// 设置
		$this->switch = $switch;

		return $this;
	}

	// 解析返回结果
	public function parseDetail(){
		$resArray = parent::fromToArray($this->tmp, 1, 1);
		
		var_dump($resArray);
	}
}