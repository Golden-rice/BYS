<?php
namespace BYS;

class Report {
	static public $log    = array(); // 各个过程的日志记录
	static public $status = array(); // 各个过程的状态记录

	static public function test(){
		echo "Class Template config success!";
	}

	// 抛出错误
	static public function error($words){
		try{
			throw new \Exception($words);
		}catch(\Exception $e){
			echo 'Throw Error :'.$e->getMessage();
		}
		exit;
	}

	static public function warning($words){
		echo $words;
	}

	static public function p($target){
		echo "<pre>";
		if(is_array($target)){
			print_r($target);
		}else{
			var_dump($target);
		}
		echo "</pre>";
	}

	static public function log($words){
		if( is_string($words) )
			array_push(self::$log, $words);
		else
			array_push(self::$log, 'Wrong Log Type');
	}

	static public function printLog(){
		$log = '';
		if(!empty(self::$log))
			foreach (self::$log as $words) {
				$log .= $words."\r";
			}
		return $log;
	}

}