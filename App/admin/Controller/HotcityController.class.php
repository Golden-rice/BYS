<?php
namespace admin\Controller;
use BYS\Controller;
class HotcityController extends Controller {
  public function plan(){
  	$this->display();
  }

  // 展示计划执行状况
  public function show(){
  	$hotcity = model('hot_city');
  	$result = $hotcity ->select();
  	echo json_encode(array('result'=>$result));
  }

  // 执行计划
  public function run(){
  	$hotcity = model('hot_city');
  	$result  = $hotcity->where("`HC_Status` = 0")->select();
  	$eterm   = reflect('eterm');
  	\BYS\Report::p($result);
  	// 所有日期未当日往后15天
  	$startDate = strtoupper( date('dM',time() + 15*24*60*60) );
  	// xfsd计划
  	// 测试一条
  	$col = $result[0];

  	if(!empty($col['HC_Cabin'])){
  		$cabin = '*'.preg_replace('/,/', '*', $col['HC_Cabin']);
  	}
  	
  	$_POST['start']      = $col['HC_Depart'];
		$_POST['end']        = $col['HC_Arrive'];
		$_POST['startDate']  = $startDate;
		$_POST['aircompany'] = $col['HC_Aircompany'];
		$_POST['private']    = ''; // 扩展
		$_POST['tripType']   = '*RT';
		$_POST['other']      = $cabin;
  	$result_xfsd         = $eterm->searchXfsdByInput(true);
  	// avh 
  	$_POST['other']      = '';
  	$_POST['endDate']    = $_POST['startDate']; 
  	$result_avh          = $eterm->searchAvhByInput(true);
  	// fsl
  	$result_fsl          = $eterm->searchFslByInput(true);

  	$result_update = $this->savePlanResult($result_xfsd, $result_avh, $result_fsl);
  	\BYS\Report::p($result_update);
  }

  private function savePlanResult($xfsd , $avh, $fsl){
  	$hotcity = model('hot_city');

		$update = array('GmtModified' => time());

		// 更新 xfsd
  	if( !empty($xfsd['id']) ){
  		$update['HC_XfsdResult_Sid']    = $xfsd['id'];
  		$update['HC_XfsdResult_Status'] = 2; // 更新成功
  	}else{
  		$update['HC_XfsdResult_Status'] = -1;
  	}

  	// 更新 avh
  	if( !empty($avh['id']) ){
  		$update['HC_AvhResult_Sid'] = $avh['id'];
  		$update['HC_AvhResult_Status'] = 2; // 更新成功
  	}else{
  		$update['HC_AvhResult_Status'] = -1;
  	}

  	// 更新 routing
  	if( !empty($fsl['id']) ){
  		foreach($fsl['array'] as $target => $val){
  			$update['HC_Routing'] = implode($val['result'],',');
  		}
  	}

  	// 判断是否全部更新
  	if(isset($update['HC_XfsdResult_Sid']) && isset($update['HC_AvhResult_Sid'])){
  		$update['HC_Status'] = 2;
  	}

  	return $hotcity->where("`HC_Status` = 0")->update($update);
  }
}