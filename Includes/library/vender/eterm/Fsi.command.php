<?php
use Eterm\Eterm;
class Fsi extends Eterm{

	// 是否有运价
	public function isTrueFareBasis($fsi){
		if(!is_string($fsi)) {
			// \BYS\Report::log('fsi的格式不正确');
			var_dump('fsi的格式不正确', $fsi);
			return;
		}

		if(empty($fsi)) {
			// \BYS\Report::log('fsi不能为空');
			var_dump('fsi不能为空');
			return;
		}
		$this->command($fsi);
		$fsiLog = $this->tmp;
		if(!preg_match("/FARE\s+(CNY|USD)\s+(\d+)/", $fsiLog, $match)){
			return array('status'=>0, 'msg'=>'指定的票价不符合运价规则', 'log'=>$fsiLog);
		}else{
			$priceArray = $this->priceDetail();
			return array('status'=>1, 'price'=>$match[2], 'log'=>$fsiLog, 'priceDetail'=>$priceArray);
		}
	}

	// 获得具体运价
	public function priceDetail(){
		$this->command('XS FSU1');
		$priceDetailSrcArray = parent::fromToArray($this->tmp);
		$fkey = 0; 
		$ekey = 0;
		$priceDetailArray = array();
		foreach ($priceDetailSrcArray as $key => $value) {
			if(preg_match("/--\sSOLD/", $value)){

				$fkey = $key;
				for($i = $fkey; $i < count($priceDetailSrcArray)-$fkey; $i++){
					if($i > $fkey+1){
						array_push($priceDetailArray, $priceDetailSrcArray[$i]);
					}
					if(preg_match("/TOTAL\sNUC/", $priceDetailSrcArray[$i])){
						$ekey = $i;
						break;
					}
				}
				break;
			}
		}

		if(!empty($priceDetailArray)){
			// 获得正确的航程价格数组，附加价格 没有farebasis 前面没有序号
			$farebasisPriceArray = array();
			$allPriceArray = array();
			foreach ($priceDetailArray as $pdKey => $pdVal) {
				if(preg_match("/\d+\s([A-Z0-9]+)\s+[CNYUSD]+\s+([0-9\.]+)\s+([A-Z\-]+)/",$pdVal, $matchPdVal)){
					array_push($farebasisPriceArray, array(
						'farebasis'=>$matchPdVal[1],
						'price'    =>$matchPdVal[2],
						'leg'      =>$matchPdVal[3],
					));
				}
				if(preg_match("/\s[CNYUSD]+\s+([0-9\.]+)\s+/",$pdVal, $matchAllPdVal)){
					array_push($allPriceArray, array(
						'price'    =>$matchAllPdVal[1],
					));
				}
			}
			return array( 'farebasisPrice' => $farebasisPriceArray,  'allPrice' => $allPriceArray, 'log'=>$this->tmp);
		}else{
			\BYS\Report::log('没解析出具体价格数据');
			return array('status'=>0, 'msg'=>\BYS\Report::printLog(), 'log'=>$priceDetailSrcArray);
		}
		// --------------- SOLD IN  /--\s.*--\s+\n/
		// -    TOTAL NUC   /TOTAL\sNUC/ 
	}

	// 解析结果
	private function parseRes(){

	}
}