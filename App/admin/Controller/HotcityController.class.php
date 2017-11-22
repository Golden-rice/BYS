<?php
namespace admin\Controller;
use BYS\Controller;
class HotcityController extends Controller {

  public function test(){
    $this->display();
  }

  public function plan(){
  	$this->display();
  }

  // price 合成政策 前台展示
  public function price(){
    $this->display();
  }

  // price 混舱
  public function mixcabin(){
    $this->display();
  }

  // 展示计划执行状况
  public function show(){
  	$hotcity = model('hot_city');
  	$result  = $hotcity->select();
  	echo json_encode(array('result'=>$result));
  }

  public function show2(){
    echo json_encode(array('result'=>$this->query('hot_city', array('conditions'=>array()) )));
  }


  // 执行计划，一天以后则更新
  public function run(){

    if(!isset($_SESSION['name'])){
      $_SESSION['name']     = 'dongmin';
      $_SESSION['password'] = '12341234'; 
      $_SESSION['resource'] = 'BJS248';
    }

  	$hotcity = model('hot_city');
  	$result  = $hotcity->where("`HC_Status` = 0 OR `GmtModified` < ".(time()-24*60*60))->limit('3')->select(); // 
    // $result  = $hotcity->limit('1')->select(); // 测试数据
  	$eterm   = reflect('eterm');
    $log     =  fopen('log.txt', 'a');

    $m_bsis_air  = model('aircompany');  // 航空公司
    $m_bsis_city = model('airport_city_code');  // 机场与城市对照表
    $m_yy        = model('yy_result'); // yy : 查询航空公司中转城市

  	// 查询日期为当月的15日，如果当天大于15号，则查询日期下个月的1号
    if(intval(date('d', time())) > 15 )
      $startDate = '01'.strtoupper(date('M', time()+15*24*60*60));
    else
    	$startDate = '15'.strtoupper(date('M', time())); // strtoupper( date('dM',time() + 15*24*60*60) ) OR 当月15号 ;

    if(empty($result)) {
      echo '无查询数据';
      $logContent = '['.date('Y-m-d H:i:s',time())."]: No plan for run!\r\n\x0a";
      fwrite($log , $logContent);
      fclose($log);
      return;
    }

    foreach($result as $col){
    	$_POST['start']      = $col['HC_Depart'];
  		$_POST['end']        = $col['HC_Arrive'];
  		$_POST['startDate']  = $startDate;
  		$_POST['aircompany'] = $col['HC_Aircompany'];
  		$_POST['private']    = ''; // 扩展
  		$_POST['tripType']   = '*RT';
  		$_POST['other']      = empty($col['HC_Cabin'])? '':'*'.preg_replace('/,/', '*', $col['HC_Cabin']);
    	$result_xfsd         = $eterm->searchXfsdByInput(true);

      // 判断 xfsd 追加一次数据
      $is_result = $this->is_continue($result_xfsd['array'][$_POST['end']], explode(',', $result[0]['HC_Cabin']));

      $result_xfsd_continue = array();
      if($is_result && is_array($is_result)){
        foreach($is_result as $cabin => $result_col){
          $_POST['startDate']     = strtoupper( date('dM', strtotime($result_col['allowDateEnd']) + 24*60*60 ) );
          $_POST['other']         = "*{$result_col['seat']}";
          $result_xfsd_continue[] = $eterm->searchXfsdByInput(true);
          // sleep(2);
          ob_flush();
          flush();
        }
      }

      // 如果设置了is_continue 恢复 startDate
      \BYS\Report::p( $result_xfsd_continue , $is_result);
      // avh 
      $_POST['startDate']  = $startDate;
    	$_POST['other']      = '';
    	$_POST['endDate']    = $_POST['startDate']; 
    	$result_avh          = $eterm->searchAvhByInput(true);

      $routing             = array();
      // CDG 戴高乐机场 PAR 城市代码<- 结果是城市代码 YY是机场代码
      // 城市代码转化成机场代码
      $arriveAirport       = $this->cityToAirport($col['HC_Arrive']);
      // 查询yy（非共享）查询中转城市
      $result_yy         = $m_yy->where("`Yy_Aircompany` = '{$col['HC_Aircompany']}' AND `Yy_IsCommon` = 0")->select('Yy_Start, Yy_End, Yy_Aircompany');
      if(!$result_yy){
        // 没有，发送YY请求 
        $eterm->setYY(true);
        $result_yy       = $m_yy->where("`Yy_Aircompany` = '{$col['HC_Aircompany']}' AND `Yy_IsCommon` = 0")->select('Yy_Start, Yy_End, Yy_Aircompany');
      }
      // 目的地为中转城市则不需要回填中转点
      $noRouting = false;
      foreach ($result_yy as $yyVal) {
        if(in_array($yyVal['Yy_End'], $arriveAirport)){ // 目的地是中转机场
          $noRouting = true;
          break;
        }
      }

      if(!$noRouting){
        // 查询中转城市 
        $res_air = $m_bsis_air->find(array('Air_Code'=>$col['HC_Aircompany']), array(), array('Air_Code', 'Air_ShortName', 'Air_Union', 'Air_Union_Type'));
        if($res_air[0]['Air_Union_Type'] == 1){ // 星空联盟可以用FSL
          // fsl
          $result_fsl        = $eterm->searchFslByInput(true);
          $fslName           = "{$col['HC_Depart']}{$col['HC_Arrive']}/{$col['HC_Aircompany']}";
          $routing           = isset($result_fsl['array'][$fslName]['result']) ? $result_fsl['array'][$fslName]['result'] : array();
        }else{

          $departAirport     = $this->cityToAirport($col['HC_Depart']);

          // 查找在该机场的中转点，默认中转点
          foreach ($result_yy as $rk => $rv) {
            if(in_array($rv['Yy_Start'], $departAirport))
              $routing[] = $rv['Yy_End'];
          }
        }
      }
      // 更新hotcity数据
    	$update = $this->savePlanResult($result_xfsd, $result_avh, $routing, $result_xfsd_continue);
      $result_update = $hotcity->where("`Id` = {$col['Id']}")->update($update);

      // 生成基础政策数据 price_source
      $this->savePriceSource(array_merge($col, $update));

      // 打印至 log 记录
      \BYS\Report::p($col);
    	\BYS\Report::p($result_update);
      $logContent = '['.date('Y-m-d H:i:s',time()).']: status:'.($result_update? 'success': 'failed')."; {$col['HC_Depart']}-{$col['HC_Arrive']}-{$col['HC_Aircompany']}; progress-xfsd:".($col['HC_XfsdResult_Status'] == 2 ? 'success': 'failed').';progress-avh:'.($col['HC_AvhResult_Status'] == 2 ? 'success': 'failed')."\r\n\x0a";

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

    if(!empty($hc_cabin) && $hc_cabin[0] !== ''){

      foreach ($array as $row){
        if(!is_array($row)) break;
        // 排除无限的适用日期
        if($row['allowDateEnd'] === '2099-12-30' || $row['allowDateEnd'] === '') continue;
        // $row['allowDateEnd'] 年份获取不到 起始日期>结束日期 = 跨年
        if(strtotime($row['allowDateStart']) > strtotime($row['allowDateEnd']) ) $row['allowDateEnd'] .= date('Y',time()+365*24*60*60);
        if(!in_array($row['seat'], $tmpXfsd) && time() + 30*60*60 > strtotime($row['allowDateEnd']) ){
          $tmpXfsd[$row['seat']] = $row;
        }
      }
    }else{
      foreach ($array as $row){
        if(!is_array($row)) break;
         // 排除无限的适用日期
        if($row['allowDateEnd'] === '2099-12-30' || $row['allowDateEnd'] === '') continue;
        // $row['allowDateEnd'] 年份获取不到 起始日期>结束日期 = 跨年
        if(strtotime($row['allowDateStart']) > strtotime($row['allowDateEnd']) ) $row['allowDateEnd'] .= date('Y',time()+365*24*60*60);
        if( (!in_array($row['seat'], $tmpXfsd) && in_array($row['seat'], $hc_cabin)) && time() + 30*60*60 > strtotime($row['allowDateEnd']) ){
          $tmpXfsd[$row['seat']] = $row;
        }
      }
    }


    if(!empty($tmpXfsd))
      return $tmpXfsd;
    return false;
  }

  // 根据跑完的及其后日期的xfsd，avh，返回更新 hot_city 表的数据
  private function savePlanResult($xfsd , $avh, $routing, $xfsd_continue = array()){
  	$hotcity = model('hot_city');

		$update = array('GmtModified' => time());

		// 更新 xfsd 
    // 此处 xfsd 仅在传一个城市作为目的地时正常，当传递多个城市时，id被替换成最新的，真实的id包含在各个目的地中
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
  		$update['HC_AvhResult_Sid'] = implode($avh['id'], ',');
  		$update['HC_AvhResult_Status'] = 2; // 更新成功
  	}else{
  		$update['HC_AvhResult_Status'] = -1;
  	}

  	// 更新 routing
  	$update['HC_Routing'] = implode($routing,',');

  	// 判断是否全部更新
  	if($update['HC_XfsdResult_Status'] === 2 && $update['HC_AvhResult_Status'] === 2){
  		$update['HC_Status'] = 2;
  	}
  	return $update;
  }

  // 如果利用post 传入 xfsd sid 则，直接返回xfsd，否则根据post 传递查询hotcity后，获得sid，再查询 xfsd
  public function searchXfsdResultByHotcity($return = false){
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
    $xfsdSmpArray  = array();

    // 根据 sid 统一查询数据库
    foreach ($sidArray as $sid) {
      $sidWhere .= " `Sid` = {$sid} OR";
      $xfsdSmpArray[$sid] = $xfsd_model->reset()->where("`Sid` = {$sid}")->group('xfsd_Cabin, xfsd_indicator, xfsd_DateEnd ')->order('xfsd_RoundFee')->select("*, COUNT(DISTINCT FareBasis, xfsd_RoundFee, xfsd_indicator, xfsd_Cabin, xfsd_DateEnd, xfsd_Rule) AS Count_duplicate ");
    }
    $xfsdArray = $xfsd_model->reset()->where(rtrim($sidWhere, 'OR'))->select();

    if(!empty($xfsdArray)){
      // 初始化
      $tmpCabin = $xfsdArray[0]['xfsd_Cabin'];
      $tmpXfsd  = array($tmpCabin=>$xfsdArray[0]);
      $sid      = $xfsdArray[0]['Sid'];

      // 利用PHP，按照sid分组
      $in_group_result = array($sid=>array());
      $in_group_smp_result = array($sid=>array($tmpCabin => $tmpXfsd));
      foreach($xfsdArray as $xfsd){
        if($sid != $xfsd['Sid']){
          $sid  = $xfsd['Sid'];
          if(!isset($in_group_result[$sid])){
            $in_group_result[$sid] = array();
          }
        }
        if($sid == $xfsd['Sid']){
          array_push($in_group_result[$sid], $xfsd);
        }
      }

      if($return){
        return array('status'=>1, 
          'inGroupResult'=>$in_group_result,   // 分组非精简        
          'inGroupSmpResult' => $xfsdSmpArray, // 分组精简
        );
      }else{
        echo json_encode(array('status'=>1, 
          'inGroupResult'=>$in_group_result,   // 分组非精简  
          'inGroupSmpResult' => $xfsdSmpArray, // 分组精简      
        ));
      }
      return;
    }
    echo json_encode(array('status'=>0, 'msg'=>'无可用数据'));
  }

  // 城市转化成机场代码
  public function cityToAirport($city){
    $m_bsis_city = model('airport_city_code');  // 机场与城市对照表
    $cityToAirport     = $m_bsis_city->where("`ACC_CityCode` = '{$city}'")->select("ACC_Code, ACC_CityCode");
    $result = array();
    if($cityToAirport)
      foreach ($cityToAirport as $cVal) {
        $result[] = $cVal['ACC_Code'];
      }
    else
      \BYS\Report::log('城市代码无法查找到机场代码');

    return $result;
  }

  public function appendHCAndPS(){
    $append         = $_POST['append'];
    $where          = $_POST['where'];
    $m_hotcity      = model('hot_city');
    $whereString    = ''; 
    if(isset($_POST['separator'])) 
      $separator = $_POST['separator'];
    else
      $separator = ',';

    foreach($where as $whereAttr => $whereVal){
      $whereString .= (is_string($whereVal) ? " `{$whereAttr}` = '$whereVal' AND" : "`{$whereAttr}` =  $whereVal AND");
    }
    $whereString    = rtrim($whereString, 'AND');
    $result         = $m_hotcity->where($whereString)->select();
    
    if($result){
      $pattern = '/'.$append['HC_XfsdResult_Sid'].'/';
      if(preg_match($pattern, $result[0]['HC_XfsdResult_Sid'])){
        echo json_encode(array('msg'=>'已存在', 'status'=>0));
        return;
      }

      // 更新 priceSource 
      $hotcity = $result[0];
      $hotcity['HC_XfsdResult_Sid'] = $append['HC_XfsdResult_Sid'];

      $result_price = $this->savePriceSource($hotcity, false);
      if($result_price){
        // 更新 hotcity
        foreach ($append as $attr => $attrVal) {
          $append[$attr] = empty($result[0][$attr]) ? $append[$attr] : $result[0][$attr].$separator.$append[$attr];
        }
        $result_hotcity = $m_hotcity->where($whereString)->update($append);
      }

      if($result_price && $result_hotcity){
        // echo json_encode(array('msg'=>'更新成功', 'status'=>1));
        return;
      }else{
        var_dump($result_price, $result_hotcity);
      }
    }else{
      echo json_encode(array('msg'=>'未查询到该条数据', 'status'=>0));
    }
  }

  // 更新 price_source 一条数据
  // 更新某条，多个where
  public function updatePriceSourceByOne(){

    $update         = $_POST['update'];
    $where          = $_POST['where'];
    $m              = model('price_source');
    $whereString    = ''; 
    foreach($where as $whereAttr => $whereVal){
      $whereString .= (is_string($whereVal) ? "`{$whereAttr}` = '$whereVal' AND" : "`{$whereAttr}` =  $whereVal AND");
    }
    if(!isset($_POST['Hid'])){
      $whereString    = rtrim($whereString, 'AND');
      $result         = $m->where($whereString)->select();
    }else{
      $hid            = $_POST['Hid'];
      $result         = $m->where("`Hid` = {$hid}")->select();
      $whereString   .= " `Hid` = {$hid}";
    }
    if($result){
      $update_result = $m->where($whereString)->update($update);
      echo json_encode(array('msg'=>'更新成功', 'status'=>1));
      return;
    }
    echo json_encode(array('msg'=>'更新失败，未查到该条数据', 'status'=>0));
  }

  // 批量更新 price_source 多条数据
  public function updatePriceSourceByAll(){
    echo json_encode(array(
      'result'=>$this->updates('price_source', $_POST['updates']),
      'msg'   => \BYS\Report::printLog() === '' ? '更新成功' : \BYS\Report::printLog()
    ));
  }

  public function canSavePriceSource($hotcity){
    $m_price_source = model('price_source');

    if($m_price_source->where("`Hid`= {$hotcity['Id']}")->select()){
      echo json_encode(array('msg'=>'已存在', 'status'=>2, 'hid'=>$hotcity['Id']));
      return false;
    } 

    if(!isset($hotcity['HC_XfsdResult_Sid'])) return false;

    return true;
  }

  // 跑完plan，生成相应的精简且不分组的price数据，保存
  public function savePriceSource($hotcity, $check=true){
    $m_price_source = model('price_source');
    if($check && !$this->canSavePriceSource($hotcity)) return;
    
    $_POST['sid']    = $hotcity['HC_XfsdResult_Sid'];
    $arrayXfsdResult = $this->searchXfsdResultByHotcity(true);
    $array           = array();  // 待添加的xfsd数据
    $addAll          = array();  // 合成后待添加的数据

    if(!isset($arrayXfsdResult['inGroupSmpResult'])) {
      var_dump($arrayXfsdResult);
      return false;
    }
    foreach ($arrayXfsdResult['inGroupSmpResult'] as $key => $xfsdInGroup) {
      if(!empty($xfsdInGroup)){
        foreach ($xfsdInGroup as $key => $xfsd) {
          array_push($array, $xfsd);
        }
      }
    }

    // 如果有中转，则按照中转再次划分
    if($hotcity['HC_Routing'] !== '')  $stayArray = explode(',',$hotcity['HC_Routing']);

    // 生成准备保存的数据
    foreach ($array as $num => $value) {
      preg_match("/\/(\d{1,2}\w{3})\//", $value['Command'], $fareDateMatch);
      // from XFareEtermPriceDetailDTO
      $add = array(
        //  fareKey 关键字：dep_city/arr_city/airline/pax_type/source/source_office/source_agreement/other(其他字段)/fare_date
        'FareKey'                 => $value['FareKey'], 
        // 运价的时间，格式是 YYYYMM 201510，查询时间
        'FareDate'                => isset($fareDateMatch[1])?$fareDateMatch[1]: '',
        // 目标舱位
        'FareCabin'               => $hotcity['HC_Cabin'],
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
        'DepType'                 => $hotcity['HC_Depart'],
        'ArrType'                 => $hotcity['HC_Arrive'],
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
        'Flight'                  => '',
        'Command'                 => $value['Command'],
        'Hid'                     => $hotcity['Id'],
        'Rule'                    => $value['xfsd_Rule'],
      );
      if(isset($stayArray) && count($stayArray) > 0){
        foreach ($stayArray as $stay) {
          $add['Stay'] = $stay;
          $addAll[] = $add; 
        }
      }else{
        $addAll[] = $add;
      }

    }
    $m_price_source->addAll($addAll);
    echo json_encode(array('msg'=>'保存成功', 'status'=>1, 'hid'=>$hotcity['Id']));
    return true;
  }

  // 查询source
  public function searchPriceSource(){
    $hid    = $_POST['hid'];
    $m      = model('price_source');
    $result = $m->where("`Hid`={$hid}")->select();
    if($result)
      echo json_encode(array('result'=>$result, 'status'=>1));
    else
      echo json_encode(array('msg'=>'出现错误', 'status'=>0));
  }

  // 查询 hotcity byid
  public function searchHotCityById(){
    $id     = $_POST['id'];
    $m      = model('hot_city');
    $result = $m->where("`Id`={$id}")->select();
    if($result)
      echo json_encode(array('result'=>$result, 'status'=>1));
    else
      echo json_encode(array('msg'=>'出现错误', 'status'=>0));
  }

  // 查询 混舱模板
  public function selectMixCabinTpl(){
    $m      = model('ota_tpl');
    $result = $m->select();
    if($result)
      echo json_encode(array('result'=>$result, 'status'=>1));
    else
      echo json_encode(array('msg'=>'出现错误', 'status'=>0));
  }

  // 验证fsi
  public function checkfsi(){
    $fsiInput = preg_replace("/\\\\r/", "\n", $_POST['fsi']);
      
    if($fsiInput){
      import('vender/eterm/app.php');
      $eterm   = reflect('eterm');
      $fsi     = new \Fsi();      
      // 获取汇率
      // 获取fsi 
      $result = $fsi->isTrueFareBasis($fsiInput);

      echo json_encode($result);
    }
  }

  public function mkSS($sk){
    // SS  AA180  O   15NOV  PEKLAX GK1/   1830 1515                                   
    // SS  AA181  O   25NOV  LAXPEK GK1/   1055 1620+1 
    $string = '';
    $aircompany    = $sk[0]['aircompany']; 
    $needCommonAir = array('AF','KL');
    // AF KL 内陆匹配舱位需要更改
    if(in_array($aircompany, $needCommonAir) && count($sk) === 4){
      $m_cabin = model('cabin_rule');

      // $MatchInnerCabin[0]['Cab_Code']
      $m_cabin->prepare("SELECT Cab_Code FROM basis_cabin_rule,(SELECT MatchInnerId AS Id FROM basis_cabin_rule WHERE Cab_AircompanyCode = '{$aircompany}' AND Cab_Code = '{$sk[1]['cabin']}' AND IsMatchInner = 1) AS A WHERE Cab_Id = A.Id");
      $OutboundMatchInnerCabin = $m_cabin->execute();
      $sk[1]['cabin'] = $OutboundMatchInnerCabin ? $OutboundMatchInnerCabin[0]['Cab_Code'] : $sk[2]['cabin'];
      $m_cabin->prepare("SELECT Cab_Code FROM basis_cabin_rule,(SELECT MatchInnerId AS Id FROM basis_cabin_rule WHERE Cab_AircompanyCode = '{$aircompany}' AND Cab_Code = '{$sk[2]['cabin']}' AND IsMatchInner = 1) AS A WHERE Cab_Id = A.Id");
      $inboundMatchInnerCabin = $m_cabin->execute();
      $sk[2]['cabin'] = $inboundMatchInnerCabin ? $inboundMatchInnerCabin[0]['Cab_Code'] : $sk[2]['cabin'];
    }

    foreach ($sk as $val) {
      $string .= "SS  {$val['flight']}  {$val['cabin']}    {$val['date']}  {$val['depart']}{$val['arrive']} GK1/   {$val['departTime']} {$val['arriveTime']}\r";
    }
    
    return $string;
  }

  // 取Sk 第一个行程正确的数据
  public function rtTrueSk($sk, $noStay){
    // AF KL 不需要过滤共享航班
    $aircompany    = $sk[0][0]['aircompany']; 
    $needCommonAir = array('AF','KL');
    $result        = array();
    if(in_array($aircompany, $needCommonAir)){
      foreach ($sk as $key => $val) {
        if($noStay){
            $result = $val;
            break;        
        }

        if(count($val) === 2 ){
            $result = $val;
            break;
        }
      }
    }else{
      foreach ($sk as $key => $val) {
        if($noStay){
          // 非共享航班过滤
          if($val[0]['isCommon'] === 0 && $val[1]['isCommon'] === 0){
            $result = $val;
            break;    
          }    
        }

        if(count($val) === 2 ){
          // 非共享航班过滤
          if($val[0]['isCommon'] === 0 && $val[1]['isCommon'] === 0){
            $result = $val;
            break;
          }
        }
      }
    }
    return $result;
  }

  // 验证qte
  public function checkqte(){

    $query          = $_POST;
    $eterm          = reflect('eterm');
    // 去程
    $outSk          = $eterm->searchSkByInput(true);
    if($outSk)
      $outSkVal     = $this->rtTrueSk($outSk['array'], $query['stay'] === '');

    // 回程
    $_POST['start'] = $query['end'];
    $_POST['end']   = $query['start'];
    $inSk           = $eterm->searchSkByInput(true);
    if($inSk)
      $inSkVal      = $this->rtTrueSk($inSk['array'], $query['stay'] === '');

    // 默认第一个为需要的数据
    if(isset($outSkVal) && isset($inSkVal) ){
      // 更新回程日期
      // 如果为AF，则改为6天或是周中周末
      foreach ($outSkVal as $k => $val) {
        if(isset($inSkVal[$k]) && $outSkVal[$k]['date'] === $inSkVal[$k]['date']){
          $curInSkValDateStamp = strtotime($inSkVal[$k]['date']);
          // 如果回程日期在周中周末，
          if(isset($query['aircompany']) && in_array($query['aircompany'], array('AF','KL')) ){
            if(in_array(date('N', $curInSkValDateStamp + 3*24*60*60), array(7,1)))
              $inSkVal[$k]['date'] = strtoupper(date('dM', $curInSkValDateStamp + 3*24*60*60));
            else
              $inSkVal[$k]['date'] = strtoupper(date('dM', $curInSkValDateStamp + 6*24*60*60));
          }else{
            $inSkVal[$k]['date'] = strtoupper(date('dM', $curInSkValDateStamp + 3*24*60*60));
          }
        }
      }
      // 扩展字段
      $routing = array_merge($outSkVal, $inSkVal);
      foreach ($routing as $key => $value) {
        $routing[$key]['cabin'] = $query['cabin'];
      }

      // 发送 ss -> qte -> fsu1 ->ig
      $qt  = new \Qt();
      $fsi = new \Fsi();

      $ssString = $this->mkSS($routing);

      if(empty($ssString)){
        echo json_encode(array('status'=>-1, 'msg'=>"无适用航班\r".\BYS\Report::printLog(), 'log'=>json_encode(array_merge($outSk, $inSk)) ));
        return;        
      }

      $price = $qt->ss($ssString, $routing);
      // 因为存在多个运价，所以改为用totel判断
      if(isset($price['price']['totalFee']) && !empty($price['price']['totalFee'])){
        $priceArray = array();
        // 延迟一秒发送
        sleep(1);
        $priceArray = $fsi->priceDetail($price['price']['index']);
        echo json_encode(array('status'=>1, 'price'=>$price['price'], 'priceDetail'=>$priceArray, 'log'=>$price['log'], 'msg'=>\BYS\Report::printLog() ));
      }else{
        echo json_encode(array('status'=>0, 'msg'=>"指定的票价不符合运价规则\r".\BYS\Report::printLog(), 'log'=>$price['log']));
      }
      ob_flush();
      flush();
      sleep(1);
      $qt->command('IG');
    }
    else{
      \BYS\Report::log('无使用SK数据');
      echo json_encode(array('status'=>0, 'msg'=>"指定的票价不符合运价规则\r".\BYS\Report::printLog()));
    }

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

  // 批量更新 price_source 多条数据
  // where 与 update 的数组的索引必须一一对应，且 where 与 update 仅只能有一条
  public function updatePriceSource(){
    $update         = $_POST['update'];
    $where          = $_POST['where'];
    $updateAttrs    = array_keys($update); 
    $whereAttrs     = array_keys($where); 
    if(count($updateAttrs) > 1 || count($whereAttrs) >1){
      var_dump('超过允许更新的长度');
    }
    $hid            = $_POST['Hid'];
    $m              = model('price_source');
    $result         = $m->where("`Hid` = {$hid}")->select();
    if($result){
      $whereArray   = array(); 
      $updateArray  = array();
      $whereAttr    = $whereAttrs[0];
      foreach(json_decode($where[$whereAttr],true) as $whereVal){
        array_push($whereArray, (is_string($whereVal) ? "`{$whereAttr}` = '$whereVal'" : "`{$whereAttr}` =  $whereVal")." AND `Hid` = {$hid}" );
      }

      // update 中某个属性更新的值与 where 一一对应，因此只能有一个update属性
      $updateAttr   = $updateAttrs[0];
      foreach(json_decode($update[$updateAttr],true) as $updateVal) { 
        array_push($updateArray, array($updateAttr => $updateVal));
      }

      $update_result = $m->updateAll($whereArray, $updateArray);

      echo json_encode(array('msg'=>'更新成功', 'status'=>1));
      return;
    }
    echo json_encode(array('msg'=>'更新失败，未查到该条数据', 'status'=>0));
  }


}