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

  // 执行计划，一天以后则更新
  public function run(){

    if(!isset($_SESSION['name'])){
      $_SESSION['name']     = 'dongmin';
      $_SESSION['password'] = '12341234'; 
      $_SESSION['resource'] = 'BJS248';
    }

  	$hotcity = model('hot_city');
  	$result  = $hotcity->where("`HC_Status` = 0 OR `GmtModified` < ".(time()-24*60*60))->limit('3')->select();
  	$eterm   = reflect('eterm');

    $log =  fopen('log.txt', 'a');

  	// 所有日期未当日往后15天
  	$startDate = '26SEP'; // strtoupper( date('dM',time() + 15*24*60*60) )
  	// xfsd计划
  	// 测试一条
    // 舱位
    if(empty($result)) {
      echo '无查询数据';
      $logContent = '['.date('Y-m-d H:i:s',time())."]: No plan for run!\x0a";
      fwrite($log , $logContent);
      fclose($log);
      return;
    }

    if($result[0] && $col = $result[0]){
    	$cabin = empty($col['HC_Cabin'])? '':'*'.preg_replace('/,/', '*', $col['HC_Cabin']);
    }

    foreach($result as $col){
    	$_POST['start']      = $col['HC_Depart'];
  		$_POST['end']        = $col['HC_Arrive'];
  		$_POST['startDate']  = $startDate;
  		$_POST['aircompany'] = $col['HC_Aircompany'];
  		$_POST['private']    = ''; // 扩展
  		$_POST['tripType']   = '*RT';
  		$_POST['other']      = $cabin;
    	$result_xfsd         = $eterm->searchXfsdByInput(true);

      // 判断 xfsd 追加一次数据
      $is_result = $this->is_continue($result_xfsd['array'][$_POST['end']], explode(',', $result[0]['HC_Cabin']));

      $result_xfsd_continue = array();
      if($is_result && is_array($is_result)){
        foreach($is_result as $cabin => $result_col){
          $_POST['startDate'] = strtoupper( date('dM', strtotime($result_col['allowDateEnd']) + 24*60*60 ) );
          $_POST['other']     = "*{$result_col['seat']}";
          $result_xfsd_continue[] = $eterm->searchXfsdByInput(true);
          // sleep(2);
          ob_flush();
          flush();
        }
      }

      // 如果设置了is_continue 恢复 startDate
      // \BYS\Report::p( $result_xfsd_continue , $is_result);
      // avh 
      $_POST['startDate']  = $startDate;
    	$_POST['other']      = '';
    	$_POST['endDate']    = $_POST['startDate']; 
    	$result_avh          = $eterm->searchAvhByInput(true);
    	// fsl
    	$result_fsl          = $eterm->searchFslByInput(true);

      // 更新hotcity数据
    	$update = $this->savePlanResult($result_xfsd, $result_avh, $result_fsl, $result_xfsd_continue);
      $result_update = $hotcity->where("`Id` = {$col['Id']}")->update($update);

      // 生成混舱数据
      // $eterm->searchPriceSource();

      // 打印至 log 记录
      \BYS\Report::p($col);
    	\BYS\Report::p($result_update);
      $logContent = '['.date('Y-m-d H:i:s',time()).']: status:'.($result_update? 'success': 'failed')."; {$col['HC_Depart']}-{$col['HC_Arrive']}-{$col['HC_Aircompany']}; progress-xfsd:".($col['HC_XfsdResult_Status'] == 2 ? 'success': 'failed').';progress-avh'.($col['HC_AvhResult_Status'] == 2 ? 'success': 'failed')."\x0a";

      // 记录结果
      fwrite($log , $logContent);
      // 增加缓存输出
      // sleep(3);
      ob_flush();
      flush();
    }

    fclose($log);
  }

  // 是否需要继续跑数据
  private function is_continue($array = array(), $hc_cabin = array()){
      // 去重数据：仅在所有舱位中取一条，默认按照第一条为选中。
      $tmpXfsd  = array();  // 临时xfsd并初始化

      if($hc_cabin != ''){
        foreach ($array as $row){
          if(!is_array($row)) break;
          if(!in_array($row['seat'], $tmpXfsd) && time() + 30*60*60 < strtotime($row['allowDateEnd']) ){
            $tmpXfsd[$row['seat']] = $row;
          }
        }
      }else{
        foreach ($array as $row){
          if(!is_array($row)) break;
          if( (!in_array($row['seat'], $tmpXfsd) && in_array($row['seat'], $hc_cabin)) && time() + 30*60*60 < strtotime($row['allowDateEnd']) ){
            $tmpXfsd[$row['seat']] = $row;
          }
        }
      }

      if(!empty($tmpXfsd))
        return $tmpXfsd;

      return false;
  }


  private function savePlanResult($xfsd , $avh, $fsl, $xfsd_continue = array()){
  	$hotcity = model('hot_city');

		$update = array('GmtModified' => time());

		// 更新 xfsd
  	if( !empty($xfsd['id']) ){
      // 存在多个
      $xfsd_sid = $xfsd['id'];
      if(!empty($xfsd_continue)){
        foreach ($xfsd_continue as $value) {
          $xfsd_sid .= ','.$value['id'];
        }
      }else{
        $xfsd_sid = $xfsd['id'];
      }
      $update['HC_XfsdResult_Sid']  = $xfsd_sid;
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


  	return $update;
  }
}