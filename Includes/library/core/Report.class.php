<?php
namespace BYS;

class Report {
	static public function test(){
		echo "Class Template config success!";
	}

	static public function error($word){
		echo $word;
	}

	static public function warning($word){
		echo $word;
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
}