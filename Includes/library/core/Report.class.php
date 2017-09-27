<?php
namespace BYS;

class Report {
	static public $log = array();

	static public function test(){
		echo "Class Template config success!";
	}

	static public function error($words){
		echo $words;
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
		array_push(self::$log, $words);
	}

	static public function printLog(){
		$log = '';
		if(!empty(self::$log))
			foreach (self::$log as $words) {
				$log .= $words;
			}
		return $log;
	}
}