<?php
namespace BYS;

class Report {
	static public function test(){
		echo "Class Template config success!";
	}

	static public function error($words){
		echo $words;
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

	}
}