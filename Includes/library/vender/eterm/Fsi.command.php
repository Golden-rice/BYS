<?php
use Eterm\Eterm;
class Fsi extends Eterm{

	// 是否有运价
	public function isTrueFareBasis($fsi){
		if(!is_string($fsi)) {
			// \BYS\Report::log('fsi的格式不正确');
			var_dump('fsi的格式不正确',$fsi);
			return;
		}

		if(empty($fsi)) {
			// \BYS\Report::log('fsi不能为空');
			var_dump('fsi不能为空');
			return;
		}
		$this->command($fsi);
		if(!preg_match("/FARE\s+(CNY|USD)\s+(\d+)/", $this->tmp, $match)){
			return array('status'=>0, 'msg'=>'指定的票价不符合运价规则', 'log'=>$this->tmp);
		}else{
			return array('status'=>1, 'price'=>$match[2]);
		}
	}

	// 解析结果
	private function parseRes(){

	}
}