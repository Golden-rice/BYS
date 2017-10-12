<?php
namespace admin\Controller;
use BYS\Controller;
class HotcityController extends Controller {
  public function plan(){
  	$this->display();
  }

  // price 合成政策 前台展示
  public function price(){
    $this->display();
  }

  public function sleep(){
    sleep(3600);
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
  	$startDate = '15'.strtoupper(date('M', time())); // strtoupper( date('dM',time() + 15*24*60*60) ) OR 当月15号 ;
  	// xfsd计划
  	// 测试一条
    // 舱位
    if(empty($result)) {
      echo '无查询数据';
      $logContent = '['.date('Y-m-d H:i:s',time())."]: No plan for run!\r\n\x0a";
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
      $logContent = '['.date('Y-m-d H:i:s',time()).']: status:'.($result_update? 'success': 'failed')."; {$col['HC_Depart']}-{$col['HC_Arrive']}-{$col['HC_Aircompany']}; progress-xfsd:".($col['HC_XfsdResult_Status'] == 2 ? 'success': 'failed').';progress-avh'.($col['HC_AvhResult_Status'] == 2 ? 'success': 'failed')."\r\n\x0a";

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
        // 排除无限的适用日期
        if($row['allowDateEnd'] == '2099-12-30') continue;
        if(!in_array($row['seat'], $tmpXfsd) && time() + 30*60*60 < strtotime($row['allowDateEnd']) ){
          $tmpXfsd[$row['seat']] = $row;
        }
      }
    }else{
      foreach ($array as $row){
        if(!is_array($row)) break;
         // 排除无限的适用日期
        if($row['allowDateEnd'] == '2099-12-30') continue;
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

  public function searchXfsdResultByHotcity(){
      $xfsd_model    = model('xfsd_result');
    if(!isset($_POST['sid'])){
      $start         = $_POST['start'];
      $end           = $_POST['end'];
      $aircompany    = $_POST['aircompany'];
      $hotcity_model = model('hot_city');
      $hotcity_result= $hotcity_model->where("`HC_Depart` = '{$start}' AND `HC_Arrive` = '{$end}' AND `HC_Aircompany` = '{$aircompany}'")->select();
      $sidArray      = explode(',', $hotcity_result[0]['HC_XfsdResult_Sid']);
    }else{
      $sidArray      = explode(',', $_POST['sid']);
    }
      
    $sidWhere      = '';
    foreach ($sidArray as $sid) {
      $sidWhere .= " `Sid` = {$sid} OR";
    }
    $xfsdArray     = $xfsd_model->where(rtrim($sidWhere, 'OR'))->select();
    
    if(!empty($xfsdArray)){
      // 初始化
      $tmpCabin = $xfsdArray[0]['xfsd_Cabin'];
      $tmpXfsd  = array($tmpCabin=>$xfsdArray[0]);
      $sid      = $xfsdArray[0]['Sid'];
      // 按照sid 分组
      $in_group_result = array($sid=>array());
      $in_group_smp_result = array($sid=>array($tmpCabin => $tmpXfsd));
      foreach($xfsdArray as $xfsd){
        if($sid != $xfsd['Sid']){
          $sid  = $xfsd['Sid'];
          $in_group_result[$sid] = array();

        }

        if($sid == $xfsd['Sid']){
          array_push($in_group_result[$sid], $xfsd);

        }
      }

      $tmpCabin = $xfsdArray[0]['xfsd_Cabin'];
      $tmpXfsd  = array($tmpCabin=>$xfsdArray[0]);
      $sid      = $xfsdArray[0]['Sid'];
      
      // 按照命令请求划分
      $result   = array($sid=>$tmpXfsd);
      // 混在一起
      $mix_result = array();
      array_push($mix_result, $xfsdArray[0]);
      // 精简
      foreach ($xfsdArray as $key => $xfsd) {
        if($xfsd['Sid'] != $sid){
          $sid      = $xfsd['Sid'];
          array_push($mix_result, $xfsd);
        }
        if($tmpCabin != $xfsd['xfsd_Cabin'] && !isset($tmpXfsd[$xfsd['xfsd_Cabin']]) ){
          $tmpCabin = $xfsd['xfsd_Cabin'];
          $tmpXfsd[$tmpCabin] = $xfsd;
          array_push($mix_result, $xfsd);
        }
      }

      echo json_encode(array('status'=>1, 
        'inGroupResult'=>$in_group_result, // 分组非精简
        'result'       =>$mix_result,            
        'allResult'    =>$xfsdArray,       // 非分组非精简
      ));
      return;
    }
    echo json_encode(array('status'=>0, 'msg'=>'发生错误'));
  }

  // 根据输入查询 price_source表
  public function searchPriceByInput(){
    $start         = $_POST['start'];
    $end           = $_POST['end'];
    $aircompany    = $_POST['aircompany'];
    $departureDate = $_POST['departureDate'];
  
  }

  public function updatePriceSource(){
    $update_src     = json_decode($_POST['update'], true);
    $hid            = $_POST['hid'];
    $m              = model('price_source');
    $result         = $m->where("`Hid` = {$hid}")->select();
    if($result){
      $whereArray   = array(); 
      $updateArray  = array();
      foreach ($update_src as $rule => $saleDate) {
        array_push($whereArray, "`Rule` = '{$rule}'");
        array_push($updateArray, array('SaleDate'=>$saleDate));
      }
      $m->updateAll($whereArray, $updateArray);
      echo json_encode(array('msg'=>'更新成功', 'status'=>1));
      return;
    }
    echo json_encode(array('msg'=>'更新失败', 'status'=>0));
  }

  public function savePriceSource(){
    $m_price_source = model('price_source');
    $m_hotcity      = model('hot_city');
    $array          = json_decode($_POST['data'], true);
    $query          = $_POST['query'];
    $where          = "`HC_Depart` = '{$query['start']}' ";
    if(!empty($query['end'])) $where .= " AND `HC_Arrive` = '{$query['end']}'";
    if(!empty($query['aircompany'])) $where .= " AND `HC_Aircompany` = '{$query['aircompany']}'";
    $hotcity_result = $m_hotcity->where($where)->select();

    if($m_price_source->where("`Hid`= {$hotcity_result[0]['Id']}")->select()){
      echo json_encode(array('msg'=>'已存在', 'status'=>2, 'hid'=>$hotcity_result[0]['Id']));
      return;
    }
    if(empty($array)) {
      echo json_encode(array('msg'=>'无数据'));
      return ;
    }
    $addAll         = array();
    foreach ($array as $num => $value) {
      preg_match("/\/(\d{2}\w{3})\//", $value['Command'], $fareDateMatch);
      // from XFareEtermPriceDetailDTO
      $addAll[] = array(
        //  fareKey 关键字：dep_city/arr_city/airline/pax_type/source/source_office/source_agreement/other(其他字段)/fare_date
        'FareKey'                 => $value['FareKey'], 
        // 运价的时间，格式是 YYYYMM 201510，查询时间
        'FareDate'                => isset($fareDateMatch[1])?$fareDateMatch[1]: '',
        // 目标舱位
        'FareCabin'               => $hotcity_result[0]['HC_Cabin'],
        'Dep'                     => $value['xfsd_Dep'],
        'Arr'                     => $value['xfsd_Arr'],
        'Airline'                 => $value['xfsd_Owner'],
        'FareBasis'               => $value['FareBasis'],
        'Cabin'                   => $value['xfsd_Cabin'],
        'PassengerType'           => 'ADT', // default 
        'SingleFare'              => $value['xfsd_SingleFee'],
        'RoundFare'               => $value['xfsd_RoundFee'],
        'Currency'                => 'CNY',
        // 'CabinFlag'               => '',
        // 'FareFlag'                => '',
        'MinStop'                 => $value['xfsd_MinStay'],
        'MaxStop'                 => $value['xfsd_MaxStay'],
        'ValidBegin'              => $value['xfsd_DateStart'],
        'ValidEnd'                => $value['xfsd_DateEnd'],
        'DepType'                 => $hotcity_result[0]['HC_Depart'],
        'ArrType'                 => $hotcity_result[0]['HC_Arrive'],
        'Direction'               => $value['xfsd_Region'],
        // 'Tpm'                     => '',
        // 'Mpm'                     => '',
        'OutboundWeek'            => $value['xfsd_Indicator'],
        'Advp'                    => $value['xfsd_Advp'],
        // 10.00  货币进位取舍(6)
        'RoundValue'              => 10.00,
        // 'NucRate'                 => '',
        // 'AtPage'                  => '',
        // 'RouteFlag'               => '',
        'Command'                 => $value['Command'],
        'Query'                   => json_encode($query),
        'Rule'                    => $value['xfsd_Rule'],
      );
    }
    $m_price_source->addAll($addAll);
    echo json_encode(array('msg'=>'保存成功', 'status'=>1, 'hid'=>$hotcity_result[0]['Id']));
  }

  public function updateSaleDate(){
    $hid    = $_POST['hid'];
    $m      = model('price_source');
    $m->prepare("SELECT FareBasis, Rule AS xfsd_Rule, Dep AS xfsd_Dep, Arr AS xfsd_Arr, Airline AS xfsd_Owner, ValidBegin AS xfsd_DateStart, SaleDate, COUNT(FareBasis) AS count FROM e_cmd_price_source WHERE `Hid` = {$hid}  GROUP BY Rule");
    $result = $m->execute();
    if($result){
      echo json_encode(array('result'=>$result, 'status'=>1));
    }
    else
      echo json_encode(array('msg'=>'出现错误', 'status'=>0));
  }

  // 查询source
  public function searchPriceSource(){
    $hid    = $_POST['hid'];
    $m      = model('price_source');
    $result = $m->where("`Hid`={$hid}")->select();
    if($result){
      echo json_encode(array('result'=>$result, 'status'=>1));
    }
    else
      echo json_encode(array('msg'=>'出现错误', 'status'=>0));
  }

  // 查询 hotcity byid
  public function searchHotCityById(){
    $id     = $_POST['id'];
    $m      = model('hot_city');
    $result = $m->where("`Id`={$id}")->select();
    if($result){
      echo json_encode(array('result'=>$result, 'status'=>1));
    }
    else
      echo json_encode(array('msg'=>'出现错误', 'status'=>0));
  }

  public function setSaleDate(){
    $start         = $_POST['start'];
    $end           = $_POST['end'];
    $aircompany    = $_POST['aircompany'];
    $departureDate = $_POST['departureDate'];
    // 利用精简后的数据 根据sid 获得 result
    $xfsd_model    = model('xfsd_result');
        // 根据舱位筛选运价结果
    $hotcity_model = model('hot_city');
    $hotcity_result= $hotcity_model->where("`HC_Depart` = '{$start}' AND `HC_Arrive` = '{$end}' AND `HC_Aircompany` = '{$aircompany}'")->select();

    if(!empty($hotcity_result[0]['HC_Cabin'])){
      $where_cabin_array = explode(',', $hotcity_result[0]['HC_Cabin']);
      $where_cabin = '(';
      foreach ($where_cabin_array as $cabin) {
        $where_cabin .= "'{$cabin}',";
      }
      $where_cabin = rtrim($where_cabin, ',').')';
    }

    $where = '';
    // 必填
    if($start != '')
      $where = "xfsd_Dep = '{$start}'";
    
    if($end != '')
      $where .= " AND xfsd_Arr = '{$end}'";
    
    if($aircompany != '')
      $where .= " AND xfsd_Owner = '{$aircompany}'";
    
    // 去程日期
    if($departureDate != ''){
      $where .= "AND xfsd_DateEnd > '{$departureDate}'";
    }else{
      $where .= "AND xfsd_DateEnd > '".date('Y-m-d',time())."'";
    }
    if(isset($where_cabin))
      $where .= " AND `xfsd_Cabin` IN {$where_cabin}";
    
    $xfsd_model->prepare("SELECT
          FareBasis, xfsd_Rule, xfsd_Dep, xfsd_Arr, xfsd_Owner, xfsd_DateStart, COUNT(FareBasis) AS count
        FROM
          e_cmd_xfsd_result
        WHERE {$where} AND  
          xfsd_SingleFee = 0
        GROUP BY
          xfsd_Rule");

    $xfsd_result =  $xfsd_model->execute();

    echo json_encode(array('result'=>$xfsd_result));

  }

  // ----------------------------- 原方法 ---------------------------
  // 根据xfsd查询政策
  public function searchPriceByXfsd(){
    $start         = $_POST['start'];
    $end           = $_POST['end'];
    $aircompany    = $_POST['aircompany'];
    $departureDate = $_POST['departureDate'];
    // 利用精简后的数据 根据sid 获得 result
    $xfsd_model    = model('xfsd_result');
    // 根据舱位筛选运价结果
    $hotcity_model = model('hot_city');
    $hotcity_result= $hotcity_model->where("`HC_Depart` = '{$start}' AND `HC_Arrive` = '{$end}' AND `HC_Aircompany` = '{$aircompany}'")->select();

    if(!empty($hotcity_result[0]['HC_Cabin'])){
      $where_cabin_array = explode(',', $hotcity_result[0]['HC_Cabin']);
      $where_cabin = '(';
      foreach ($where_cabin_array as $cabin) {
        $where_cabin .= "'{$cabin}',";
      }
      $where_cabin = rtrim($where_cabin, ',').')';
    }

    $where = '';
    // 必填
    if($start != '')
      $where = "xfsd_Dep = '{$start}'";
    
    if($end != '')
      $where .= " AND xfsd_Arr = '{$end}'";
    
    if($aircompany != '')
      $where .= " AND xfsd_Owner = '{$aircompany}'";

    // 去程日期
    if($departureDate != '')
      $where .= "AND xfsd_DateEnd > '{$departureDate}'";
    else
      $where .= "AND xfsd_DateEnd > '".date('Y-m-d',time())."'";
    
    if(isset($where_cabin))
      $where .= " AND `xfsd_Cabin` IN {$where_cabin}";

    $xfsd_model->prepare("SELECT
        *, COUNT(DISTINCT A.FareBasis, A.xfsd_RoundFee, A.xfsd_indicator, A.xfsd_Cabin, A.xfsd_DateEnd, A.xfsd_Rule) AS Count_duplicate # 去重
      FROM (
          SELECT
            *
          FROM
            e_cmd_xfsd_result
          WHERE {$where} AND 
            xfsd_SingleFee = 0
          ORDER BY 
            GmtModified DESC, xfsd_RoundFee ASC  # 显示最低价格 最新数据
        ) AS A
      GROUP BY  # 精简
        A.xfsd_Cabin,
        A.xfsd_indicator,
        A.xfsd_DateEnd 
      ORDER BY 
        A.xfsd_Cabin, A.xfsd_RoundFee ASC # 展示优化 " );
    // var_dump($xfsd_model->testSql());
    $xfsd_result = $xfsd_model->execute();

    // 按照舱位及价格已精简
    echo json_encode(array('result'=>$xfsd_result));
    /* 销售日期
    XS/FSDBJSCHI/15OCT/UA/#*GLE03MC8///#
    xs/fsn1//15
        */
  }

}