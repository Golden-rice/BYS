<?php
use Eterm\Eterm;
class Qt extends Eterm{
	public $arr = array();

	// 解析当前记录的价格
	public function qte($pnr, $note){
		if(!$pnr) return;
		$aircompany = substr($note[0]['flight'], 0, 2);
		parent::mixCommand(array("RT{$pnr}", "QTE:/*{$aircompany}"), 'a');
		if(!empty($this->tmp)){
			$log   = $this->tmp;               // 原始数据
			$price = $this->parsePrice($note); // 获得价格
			$this->arr = array_merge(array('log'=>$log, 'aircompany'=>$aircompany), $price);
			sleep(1);
			parent::command('IG');
			return $this->arr;
		}else{
			var_dump($this->tmp);
		}
		sleep(1);
		parent::command('IG');
	}

	// 解析当前记录的最低价格
	public function qtb($pnr, $note){
		if(!$pnr) return;
		$aircompany = substr($note[0]['flight'], 0, 2);
		parent::mixCommand(array("RT{$pnr}", "QTB:/{$aircompany}"), 'a');  // QTB:/{$aircompany} 会显示多个运价
		if(!empty($this->tmp)){
			// var_dump('TMP:', $this->tmp);
			$log   = $this->tmp;               // 原始数据
			$price = $this->parsePrice($note); // 获得价格

			$this->arr = array(
				'log'        => $log, 
				'aircompany' => $aircompany,
				'fareFee'    => $price['fareFee'],
				'totalFee'   => $price['totalFee'],
				'routing'    => $price['routing'],
				'currency'   => $price['currency'],
				'note'       => $price['note'],
			);

			if($price['fareFee'] === null){
				var_dump('NOTE', $note);
				var_dump('LOG', $log);
				var_dump('PRICE:', $price);
			}

			sleep(1);
			parent::command('IG');
			return $this->arr;
		}else{
			var_dump($this->tmp);
		}
		sleep(1);
		parent::command('IG');
	}

	// 利用预定接口查询价格
	public function ss($ssString = '', $note){
		if(empty($ssString)) return;
		$aircompany = $note[0]['aircompany'];

		parent::mixCommand(array("{$ssString}", "QTE:/{$aircompany}"), 'w');  
		if(!empty($this->tmp)){
			$log   = $this->tmp;               // 原始数据
			$price = $this->parsePrice($note); // 获得价格

			return array('price'=>$price, 'log'=>$log);
		}
		return false;
	}

	// 解析价格
	public function parsePrice($note){
		if(empty($note)) return;
		$list       = parent::initFile($this->tmp);
		$aircompany = substr($note[0]['flight'], 0, 2);
		$routingLen = count($note);
		$totalFee   = 0;  // 总价
	 	$fareFee    = 0;  // 票面
	 	$lowestTotalPrice = 0; // 当有多个fare时，最低的价格
	 	$routing    = array(); // routing 航路
 		$fsiKey     = -1; // 匹配结果中能组合到fsi的最新位置

 		foreach ($list as $key => $line) {
 			// 匹配行程，增加note行程的中转直达标记，找到第一个段的位置
 			if($fsiKey < 0){
 				foreach ($note as $noteKey => $noteValue) {
 					if(isset($list[$key+$noteKey])){
	 					$fsiPattern = "/S\s{$aircompany}\s{3}.*{$noteValue['cabin']}{$noteValue['date']}\s{$noteValue['depart']}{$noteValue['departTime']}[\s|>]".substr($noteValue['arriveTime'], 0, 4)."{$noteValue['arrive']}0(S|X)/";
			 			if(preg_match($fsiPattern, $list[$key+$noteKey], $fsiArr)){
				 			if(isset($fsiArr[1])){
				 				$fsiKey  = $key;
				 				// 扩展新属性
			 					$note[$noteKey]['routingType'] = $fsiArr[1];
				 			}else{
				 				$note[$noteKey]['routingType'] = '';
				 			}
			 			}
 					}
 				}
 			}

 			// 当有多个运价导致匹配不到价格时
	 		if($totalFee === 0 && $fsiKey >=0){
	 			if(preg_match("/(\d+)\s\w+\+\w+?\*\s+(\d+)\s(CNY)?\s+INCL\s+TAX/", $line, $curFareTotalFeeMatch)){
	 				// 序号2 为匹配的价格，比较出最小的，其key对应的序号为1的值
	 				if($lowestTotalPrice > 0 && $lowestTotalPrice > $curFareTotalFeeMatch[2]){
	 					$lowestTotalPrice = $curFareTotalFeeMatch[2];
		 				$index            = $curFareTotalFeeMatch[1];
	 				}

 					// 初始化
	 				if($lowestTotalPrice === 0 )
	 					$lowestTotalPrice = $curFareTotalFeeMatch[2];

	 			}
	 		}

 			// 运价，货币
 			if($fareFee == 0 ){ // && $fsiKey >= 0 当票面没有时，

 				// 无适用运价，往下即为farebasis
	 			preg_match('/\*无适用运价/', $line, $curNoPriceArr);
	 			if(isset($curNoPriceArr[1])){
	 				break;
	 			}

 				// 票面
	 			preg_match('/FARE\s+.*(CNY)\s+(\d+)/i', $line, $curFareFeeArr);
	 			if(isset($curFareFeeArr[2]) && $curFareFee = $curFareFeeArr[2]) {
	 				if( $fareFee == 0 || $curFareFee < $fareFee){
			 			$fareFee  = $curFareFee;
			 			$currency = $curFareFeeArr[1];
			 		}

		 			preg_match('/TOTAL\s+.*(CNY)\s+(\d+)/i', $list[$key+2] , $curTotalFeeArr);
		 			if(isset($curTotalFeeArr[2]) && $curTotalFee = $curTotalFeeArr[2]) {
			 			// total价格
		 				if( $totalFee == 0 || $curTotalFee < $totalFee){
			 				$totalFee = $curTotalFee;
		 				}
		 			}

	 				// 匹配到fare往上$routingLen即为fare规则
	 				for ($i=0; $i < $routingLen; $i++) { 
	 					$routingDetailString = $list[$key-$routingLen+$i];
	 					$routing[] = array( substr($routingDetailString, 1, 3) => rtrim(substr($routingDetailString, 5, 10)));
	 				}
		 			break;
	 			}

 			}
 		}

		return array(
			'index'   => isset($index) ? $index : '01',
			'fareFee' => $fareFee,
			'totalFee'=> $totalFee === 0 && isset($lowestTotalPrice) ? $lowestTotalPrice : $totalFee,
			'routing' => $routing,
			'currency'=> isset($currency) ? $currency : 'CNY',
			'note'    => $note
		);
	}

	public function initFile($dataFrom, $rangeStart = 0, $rangeEnd = 0){
		return parent::initFile($dataFrom, $rangeStart, $rangeEnd);
	}
}