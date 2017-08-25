<?php
namespace admin\Controller;
use BYS\Controller;
class EtermController extends Controller {

	// xfsd 前台展示
  public function xfsd(){
  	$this->display();
  }

  // avh 前台展示
  public function avh(){
		$this->display();
  }

  // fare使用规则 前台展示
  public function fare(){
  	$this->display();
  }

  // 获取汇率
  public function toCNY(){
  	import('vender/eterm/app.php');

  	$xfsd = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
		$xfsd->command($_POST['command'],"w", false);
		$rate = $xfsd->changePrice();
		echo json_encode(array('rate'=>$rate));
  }

  // 查看使用规则
  public function searchFsd(){
  	import('vender/eterm/app.php');

  	$fsd = new \Fsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);

		$fare       = str_replace(' ','', $_POST['fare']);
		$start      = $_POST['start'];
		$end        = $_POST['end'];
		$startDate  = $_POST['startDate'];
		$aircompany = $_POST['aircompany'];
		$from       = $_POST['from'];
		$identity   = '';

		// 扩展命令
	 	if(isset($_POST['identity'])){
	 		$identity = '<'.$_POST['identity'];
	 	}

		$date = array( 
			'fare'=> $fare,
			'start'=> $start,
			'end'=> $end,
			'startDate'=> $startDate,
			'aircompany'=> $aircompany,
			'from'=> $from
		);

		$command = 'XS/FSD'.$start.$end.'/'.$startDate .'/'.$aircompany.'/'.'#*'.$fare.$identity;

		if($from != '公布运价'){
			$command = $command.'///#'.$from;
		}

		$array = empty($_POST['index']) ? $fsd->fare(array(0,1,2), $date, $command) : $fsd->fare(array(0=>$_POST['index'],1,2), $date, $command); 	
		
		echo json_encode(array('command'=> $command,'aircompany'=> $aircompany, 'fare'=> $fare, 'array'=>$this->assignItem($array), 'data'=>$array) ); 
  }

  // 通过输入框查询xfsd 
  public function searchXfsdByInput(){
  	import('vender/eterm/app.php');

  	$xfsd       = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
		$start      = $_POST['start'];
		$endMore    = $_POST['end'];
		$startDate  = $_POST['startDate'];
		$aircompany = $_POST['aircompany'];
		$code       = $_POST['private'];
		$tripType   = $_POST['tripType'];
		$other      = $_POST['other'];
		$ab_flag    = preg_match("/[\/]2|[\/]2[\/]|2[\/]/",$other, $str) ? true:false;
		$endArr     = explode(',', $endMore);  // 多地点录入时
		$array      = array();                 // 解析结果的数组，支持多个地点
		
		foreach($endArr as $end){
			// 生成命令
			$command = $this->toXfsdCommand($start, $end, $startDate, $aircompany, $tripType, $code, $other );

			// 开始查询
			$xfsd->command($command, "w");

			$result = $this->hasXfsdSource(array('command' => $command, 'firstPage' => $xfsd->getFirstPage()));

			if( $result ){ 
			// 数据库中有数据，查询旧source数据作为tmp
				$xfsd->wtTmp($result);
				$resultArr = $ab_flag ? $xfsd->analysis(array(2,3,4)) : $xfsd->analysis(array(2,3));
				// 声明
				$array[$end] = $resultArr;
			}else{
			// 继续查询新数据
				// 继续查询全部数据并解析为二维数组
				$resultArr = $ab_flag ? $xfsd->analysis(array(1,2,3,4)) : $xfsd->analysis(array(1,2,3));
				// 声明
				$array[$end] = $resultArr;
				$array[$end]['id'] = $this->saveXfsdSource(array('source' => $xfsd->readSource(), 'command' => $command)); // 储存至数据库
			}
			// 封装基础数据格式
			$array[$end]['from']       = $code==''?'':$code;
			$array[$end]['aircompany'] = $aircompany;
			$array[$end]['startDate']  = $startDate;
			$array[$end]['length']     = count($resultArr);
			$array[$end]['command']    = $command;
			$array[$end]['other']      = $other;
		}

		// 保存解析结果
		$this->saveXfsdResult($array);
		echo json_encode(array('array'=>$array, 'time'=>'更新时间：'.date('Y-m-d H:i:s', $xfsd->fileTime)) );
  }

  // 储存xfsd source资源
  private function saveXfsdSource($array = array()){
  	$m_xfsd = model('xfsd_source');
  	$add = array(
  		'office'  => $_SESSION['resource'],
  		'status'  => 2,
  		'command' => isset($array['command'])? $array['command'] : '',
  		'detail'  => isset($array['source'])? $array['source']: '',
  	);
  	return $m_xfsd->add($add);
  }

  // 储存xfsd 解析结果，用是否含id来区分是否保存
  private function saveXfsdResult($array){
  	if( count($array) == 0 ) return;

  	$m_xfsd = model('xfsd_result');
  	$addAll = array();

  	foreach ($array as $end => $list) {
  		if(!isset($list['id'])) continue;
  		for($i = 0; $i < count($list)-6; $i++){
  			if(!isset($list[$i])) {
  				var_dump($list[$i]);
  				break;
  			}
  			$value = $list[$i];
	    	$addAll[] = array(
	    		//  fareKey 关键字：dep_city/arr_city/airline/pax_type/source/source_office/source_agreement/other(其他字段)/fare_date
					'FareKey'    => "{$value['start']}/{$value['end']}/{$list['aircompany']}/ADT/{$_SESSION['resource']}/{$list['from']}/{$list['other']}/".date('Ymd',strtotime($list['startDate'])), 
					// 命令
					'Command'    => $list['command'],
					// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
					'Status'     => 2,
					// OFFICE 号
					'Office'     => $_SESSION['resource'],
					// source id
					'Sid'        => $list['id'],
					// fare FareBasis
					'FareBasis'      => $value['fare'],
					// special 特殊规则
					'xfsd_Special'   => $value['special'],
					// advp 提前出票
					'xfsd_Advp'      => $value['ADVPDay'],
					// allowDateStart 适用日期起始
					'xfsd_DateStart' => date('Y-m-d',strtotime($value['allowDateStart'])),
					// allowDateEnd 适用日期结束
					'xfsd_DateEnd'   => date('Y-m-d',strtotime($value['allowDateEnd'])),
					// backLineFee 往返费用
					'xfsd_RoundFee'  => $value['backLineFee'],
					// singleLineFee 单程费用
					'xfsd_SingleFee' => $value['singleLineFee'],
					// start 出发
					'xfsd_Dep'       => $value['start'],
					// end 到达
					'xfsd_Arr'       => $value['end'],
					// aircompany 航空公司
					'xfsd_Owner'     => $list['aircompany'],
					// direction 区域
				  'xfsd_Region'    => $value['direction'],
					// allowWeek 作用点
				  'xfsd_Indicator' => $value['allowWeek'],
					// maxStay 最大停留
				  'xfsd_MaxStay'   => $value['maxStay'],
					// minStay 最短停留
				 	'xfsd_MinStay'   => $value['minStay'],
					// seat 舱位
				 	'xfsd_Cabin'     => $value['seat'],
					// fromCode
				 	'xfsd_Code'      => $value['seat']
	    	);
  		}
  	}

  	$m_xfsd->addAll($addAll);
  }

	// 查询command，如果存在，且不只一条，比较他们的firstpage，如果相同，则返回这个source。如果不相同，则返回false
	// 更新功能
  private function hasXfsdSource($array = array()){
  	$m_xfsd = model('xfsd_source');
  	$result = $m_xfsd ->where('`command` ="'.$array['command'].'" ')->select();

  	// 为空时
  	if(!$result || count($result) == 0) return false;

  	foreach ($result as $rows => $cols) {
  		if(isset($array['firstPage']) && $flength = strlen($array['firstPage'])  ){
  			if ( isset($cols['Detail']) && $array['firstPage'] == substr($cols['Detail'], 0 , $flength) ) 
  				return $cols['Detail'];
  		}
  	}
  	return false;
  }

  // 通过command查询xfsd
  public function searchXfsdByCommand(){
  	import('vender/eterm/app.php');
  	$xfsd = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);

  	$command = $_POST['command'];

		// 匹配是否含有身份
		if(preg_match_all('/<(SD|CH|IN|ADT|ZZ)/', $command, $str)) $str = $str[1][0];

		$str = preg_match_all('/(\/\/\/)#(\w+\*?\w+)/',substr($command, 19), $arr);

		if($remove){
			$xfsd->removeRuntime($command);
		}
		$xfsd->command($command, "w", false);

		$resultArr                    = $xfsd->analysis(array(1,2,3));
		$array                        = array( "OWEND" => $resultArr );
		$array["OWEND"]['from']       = $code==''?'公布运价':$code;
		$array["OWEND"]['aircompany'] = $aircompany;
		$array["OWEND"]['startDate']  = $startDate;
		$array["OWEND"]['length']     = count($resultArr);
		$array["OWEND"]['command']    = $command;
  }

  public function toXfsdCommand( $start, $end, $startDate, $aircompany, $tripType, $code, $other ){
		// NUC 数值
		$other .= "/NUC";
		// 根据出发到达组合成合适的命令
		if(!empty($tripType)) $tripType = '/'.$tripType;
		return $code ? 'XS/FSD'.$start.$end.'/'.$startDate.'/'.$aircompany.$tripType.'/NEGO/X///#'.$code.'/'.$other : 
	                 'XS/FSD'.$start.$end.'/'.$startDate.'/'.$aircompany.$tripType.'/X'.'/'.$other ;
	}

	// 通过输入框查询avh 
	public function searchAvhByInput(){
		import('vender/eterm/app.php');
		$avh = new \Avh($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);

		// 读取avh数据
		if($_POST['dosubmit'] == 'cabin'){

			$start      = $_POST['start']; 
			$end        = $_POST['end'];
			$startDate  = $_POST['startDate'];        
			$endDate    = $_POST['endDate']; 
			$airCompany = $_POST['airCompany'];   
			$other      = $_POST['other'];    

			// 多目的地
			if(preg_match("/,/", $end))
				$endArr   = explode(",", $end);

			// 多出发地
			if(preg_match("/,/", $start))
				$startArr = explode(",", $start);

			// 其他参数
			if($other)
				$other    = '/'.$other;

	 		$startTime  = isset($startTime) ? '/'.$startTime : "";
			$during     = (strtotime($endDate)-strtotime($startDate))/(24*60*60);
			$array      = array();
			$repeat     = array('data' => array($end), 'type'=> 'signle', 'pos'=>'end');

			if( isset($endArr) )
				$repeat = array('data'=>$endArr, 'type'=>'array','pos'=>'end');
			else if( isset($startArr) )
				$repeat = array('data'=>$startArr, 'type'=>'array','pos'=>'start');
			
			// 循环 
			foreach ($repeat['data'] as $value) {
				$array[$value] = array();

				// 拼装命令及执行
				for ($i = 0; $i <= $during; $i++) { 
					$days    = strtotime($startDate) +$i*24*60*60;
					$m       = strtoupper(date('M',$days));
					$d       = strtoupper(date('d',$days));
					$date    = $d.$m;
					$command = $repeat['pos'] == 'start' ? 'AVH/'.$value.$end.$date.$startTime.$other.'/'.$airCompany : 'AVH/'.$start.$value.$date.$startTime.$other.'/'.$airCompany;
					$result  = $this->hasAvhSource(array('command'=>$command));

					if ( isset($result['GmtModified']) && $result['GmtModified'] + 24*60*6*1000 < time() ){ 
						// 有且存储时间大于一天，更新
						$avh->command($command, "w", false);
						$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
						$id = $this->updateAvhSource(array('source' => $avh->readSource(), 'command' => $command));
						// 更新result
						$this->updateAvhResult($array[$value], $id, $command);
					}
					elseif( isset($result['GmtModified']) && $result['GmtModified'] + 24*60*6*1000 >= time()){
						// 有，但是储存时间不大于一天，读取数据库数据
						$avh->wtTmp($result['Detail']);
						$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
					}
					else{
						// 没有，新增
						$avh->command($command, "w", false);
						$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
						$id = $this->saveAvhSource(array('source' => $avh->readSource(), 'command' => $command)); // 储存至数据库
						$this->saveAvhResult($array[$value], $id, $command);
					}
				}
			}
			echo json_encode(array('array'=>$array, "type"=>'array'));
		}	
	}

	// 储存 avh_result
	public function saveAvhResult($array = array(), $id = 0, $command = ''){
  	if( count($array) == 0 ) return;

  	$m_avh = model('avh_result');
  	$addAll = array();
  	$totel = 0;
  	// 日期
  	foreach($array as $day){
  		// 航程
  		foreach ($day as $index => $list) {
	  		// 航段
	  		for($i = 0; $i < count($list); $i++){
	  			$value = $list[$i]; // 航段
	  			$addAll[$totel++] = array(
						// 命令
						'Command'       =>$command,
						// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
						'Status'        => 2,
						// OFFICE 号
						'Office'        => $_SESSION['resource'],
	  				// Sid
	  				'Sid'           => $id,
						// start 出发
						'avh_Dep'       => $value['start'] == ''? $list[$i-1]['end'] :$value['start'],
						// end 到达
						'avh_Arr'       => $value['end'],
						// startTime 出发时间
						'avh_DepTime'   => $value['startTime'],
						// endTime 到达时间
						'avh_ArrTime'   => $value['endTime'],
						// startDate 出发日期
						'avh_Date'      => date('Y-m-d',strtotime($value['startDate'])),
						// flight 航班号
						'avh_Flight'    => $value['startTime'],
						// carrier 实际承运
						'avh_Operation' => $value['carrier'],
						// cabin 舱位
						'avh_Cabin'     => json_encode($value['cabin']),
						// airType 机型
						'avh_AirType'   => $value['airType'],
						// 是否直达
					  'avh_IsDirect'  => 1,
					  // 航段组合id sid - id
					  'avh_Rid'       => '0-0-0',
	  			);
	  			if( count($list) >1 ){
	  				$addAll[$totel-1]['avh_IsDirect'] = 0;
	  				$addAll[$totel-1]['avh_Rid']      = $id.'-'.$index.'-'.$i;
	  			}
	  				
	  		}
  		}
  	}

  	$m_avh -> addAll($addAll);
	}

	// 更新 avh_result
	public function updateAvhResult($array = array(), $id = 0, $command = ''){
  	if( count($array) == 0 ) return;
		
		// 删除 
		$this->deleteAvhResult($id);

		// 再插入
		$this->saveAvhResult($array, $id, $command);

	}

	public function deleteAvhResult($id = 0){
		$m_avh = model('avh_result');
		$result = $m_avh->where("`sid` = {$id}")->delete();
	}

	// 更新 avh_source
	private function updateAvhSource($array = array()){
		$m_avh = model('avh_source');
  	$update = array(
  		'detail' => isset($array['source'])? $array['source']: '',
  		'GmtModified' => time()
  	);
  	$m_avh->where('`command` = "'.$array['command'].'" ')->update($update);
  	// 仅有一条
  	$result = $m_avh->where('`command` = "'.$array['command'].'" ')->select();
  	return $result['Id'];
	}

	// 保存 avh_source
	private function saveAvhSource($array = array()){
		$m_avh = model('avh_source');
  	$add = array(
  		'office' => $_SESSION['resource'],
  		'status' => 2,
  		'command'=> isset($array['command'])? $array['command'] : '',
  		'detail' => isset($array['source'])? $array['source']: '',
  	);
  	return $m_avh->add($add);
	}

	// 是否有 avh_source，如果有且生产时间大于一天则更新，如果有但生产时间没有大于一天则读取，没有则添加新数据
	private function hasAvhSource($array = array()){
  	$m_avh = model('avh_source');
  	$result = $m_avh ->where('`command` ="'.$array['command'].'" ')->select();

  	// 为空返回false
  	if(!$result || count($result) == 0) return false;

		if ( isset($result['Command']) && $array['command'] == $result['Command'] ) 
			return array('Detail' =>$result['Detail'], 'GmtModified' =>$result['GmtModified'] );

  	return false;
	}

	// 新增混舱
	public function addMixCabin(){
		if(isset($_POST['data']) && $_POST['action'] == 'add'){
			if(!isset($_SESSION['data']) )  $_SESSION['data'] = array();
			if( count($_SESSION['data']) < 2  )
				$_SESSION['data'][] = $_POST['data'];
		}

		if(isset($_SESSION['data'])){
			echo json_encode($_SESSION['data']);
		}
	}

	// 删除混舱
	public function deleteMixCabin(){
		if( isset($_GET['delete']) ){
			unset($_SESSION['data'][$_GET['delete']]);
		}
	}

	// 清空混舱数据
	public function clearMixCabin(){
		if(isset($_POST['clear'])){
			unset($_SESSION['data']);
		}
	}

	// 显示混舱数据
	public function showMixCabin(){
	
		$data = $_SESSION['data'];
		$array = array(); // 混舱后

		if(empty($data)) {
			echo 'ERROR: No Session Data !';
			exit;
		}


		if(count($data) == 2){ // 最多2个航段

			// 合并航段 (A+B)
			foreach ($data as $num => $value) {
				foreach ($data[$num] as $end => $arr) {

					for ($line = 0; $line < $arr['length']; $line++) {
						$data_end_merge[] = $arr[$line];
					}
				}
			}

			// (A+B)^2
			for ($line = 0; $line < count($data_end_merge); $line++) {
				for($line_match = 0; $line_match < count($data_end_merge); $line_match++){
						$array[] = $this->matchRule($data_end_merge[$line], $data_end_merge[$line_match]);
				}
			}

		}else if(count($data) == 1 ){
			// A^2: $data[0] * $data[0] 不一定是0，中间存在删除操作时
			foreach ($data as $num => $value) {
				$data[0] = $data[$num];
			}

			foreach ($data[0] as $end => $arr) {
				for ($line = 0; $line < $arr['length']; $line++) {
					for($line_match = 0; $line_match < $arr['length']; $line_match++){
							$array[] = $this->matchRule($data[0][$end][$line], $data[0][$end][$line_match]);
					}
				}
			}	

		}else{
			echo 'ERROR: Not True Count of Session !';
		}

		$_SESSION['mixCabin'] = $array;

		$this->smarty->assign('data', json_encode($array));
		$this->smarty->assign('org_data', json_encode($data));

		$this->display('eterm/mixCabin');
	}

	// 显示混舱套模板后的数据
	public function showMixCabinTpl(){

		if(empty($_SESSION['mixCabin'])) return;

		if(isset($_POST['tpl']) && isset($_SESSION['data'])){
			$tpl = json_decode($_POST['tpl'], true);

			$array = $_SESSION['mixCabin'];
			$tplName = $_POST['tplName'];
			$typeName = $_POST['typeName'];

			if( preg_match("/taobao/", $tplName) ){
				foreach ($array as $key => $value) {
					// $arrayByTpl[] = $array[$key];
					$tpl['outFileCode']   = "";
					$tpl['originLand']    = $value['start'];
					$tpl['destination']   = $value['end'];
					$tpl['cabin']         = $value['seat'];
					$tpl['FareBasis']      = $value['fare'];
					$tpl['flightDateRestrict4Dep']   = $value['allowWeek_1'];
					$tpl['flightDateRestrict4Ret']   = $value['allowWeek_2'];
					$tpl['minStay']       = $value['minStay'];
					$tpl['maxStay']       = $value['maxStay'];
					$tpl['ticketPrice']   = $value['backLineFee'] != ''? $value['backLineFee'] : $value['singleLineFee'];  
					$tpl['childPrice']    = $tpl['ticketPrice'];
					$arrayByTpl[] = $tpl;
					
				}
			}else if( preg_match("/xiecheng/", $tplName) ){
				foreach ($array as $key => $value) {
					$tpl['outFileCode']    = "";
					$tpl['DepartCity']     = $value['start'];
					$tpl['ArriveCity']     = $value['end'];
					// Routing 航路
					preg_match_all("/(\w)\,(\w)/",$value['seat'], $s);
					$seat = isset($s[1][0]) && $s[1][0] == $s[2][0] ? $s[1][0]: $value['seat'];
					$tpl['RoutingClass']   = $seat;
					$tpl['FareBasis']      = $value['fare'];
					$tpl['OutboundDayTime']= $value['allowWeek_1'];
					$tpl['InboundDayTime'] = $value['allowWeek_2'];
					// 去程旅行日期
					// 回程旅行日期
					// 销售日期
					// 乘客资质
					$tpl['MinStay']        = $array[$key]['minStay'];
					$tpl['MaxStay']        = $array[$key]['maxStay'];
					$tpl['SalesPrice']     = $array[$key]['backLineFee'] != ''? $array[$key]['backLineFee'] : $array[$key]['singleLineFee'];  
					// 出票时限
					// 成人去程行李额
					// 成人回程行李额
					$arrayByTpl[] = $tpl;
				}
			}



			$this->smarty->assign('data', json_encode($arrayByTpl));
			$this->assign('tplMatch', json_encode(array('tplName'=>$tplName, 'typeName'=>$typeName)));
		}

		$this->display('eterm/mixCabinByTpl');
	}


	private function matchRule($array1, $array2){
		// 混舱规则
		if(empty($array1) || empty($array2)) {
			echo 'empty';
			return;
		}

		$stay = '';
		$start_2 = '';
		$end_2 = '';
		$stay_2 = '';

		$array = array();

		$stayMatch = array(
			'EK' => array(
				'aircompany' => 'EK',
				'stay' => 'DXB'
				),
			'EY' => array(
				'aircompany' => 'EY',
				'stay' => 'DXB'
				),
		);

		if($array1['start'] != $array2['start']){
			$start_2 = $array2['start'];
		}

		if($array1['end'] != $array2['end']){
			$end_2 = $array2['end'];
		}


		if($array1['aircompany'] == $array2['aircompany']){
			// 相同航空公司
			if(isset($stayMatch[$array1['aircompany']]) && $array1['end'] != $stayMatch[$array1['aircompany']]['stay']){ // 有该航司中转城市，且目的地不是中转城市
				$stay = $stayMatch[$array1['aircompany']]['stay'];
			}

			// 待测试
			if(!empty($end_2)){
				if(isset($stayMatch[$array2['aircompany']]) && $array2['end'] != $stayMatch[$array2['aircompany']]['stay']){ // 有该航司中转城市，且目的地不是中转城市
					$stay_2 = $stayMatch[$array2['aircompany']]['stay'];
				}
			}

		}else{
			// 不同航空公司
			echo '不能进行不同航空公司混舱规则！';
			exit;
		}

		$array = array(
				'fare'          => $array1['fare'].'+'.$array2['fare'],
				'ADVPDay'       => strtotime(preg_replace("/D/","day",$array1['ADVPDay'])) > strtotime(preg_replace("/D/","day",$array2['ADVPDay'])) ? preg_replace("/D/","",$array1['ADVPDay']): preg_replace("/D/","",$array2['ADVPDay']),
				'singleLineFee' => $array1['singleLineFee'] == ""? "":($array1['singleLineFee']+$array2['singleLineFee'])/2,
				'backLineFee'   => $array1['backLineFee'] == "" ? "":($array1['backLineFee']+$array2['backLineFee'])/2,
				'seat'          => $array1['seat'].','.$array2['seat'],
				'minStay'       => strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array1['minStay'])) > strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array2['minStay'])) ? $array2['minStay'] : $array1['minStay'],
				'maxStay'       => strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array1['maxStay'])) < strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array2['maxStay'])) ? $array1['maxStay'] : $array2['maxStay'],
				'allowDateStart'=> strtotime($array1['allowDateStart']) > strtotime($array2['allowDateStart'])? $array1['allowDateStart']:$array2['allowDateStart'],
				'allowDateEnd'  => strtotime($array1['allowDateEnd']) < strtotime($array2['allowDateEnd'])? $array1['allowDateEnd']:$array2['allowDateEnd'],
				'allowWeek_1'   => $array1['allowWeek'],
				'allowWeek_2'   => $array2['allowWeek'],
				'start'         => $array1['start'],
				'end'           => $array1['end'],
				'stay'          => $stay,
			);

		if( !empty($start_2) ){

			$array['start_2'] = $array2['start'];

		}
		if(!empty($end_2)){

			$array['end_2'] = $array2['end'];

		}
		if(!empty($stay_2)){ 

			$array['stay_2'] = $stay_2;

		}
		
		return $array;

	}


	// 使用规则，备注装填
	public function assignItem($arr){
		if(empty($arr)){
			return;
		}

		$row = 0;   // 行号
		$array = array(
			1 =>array('caption' => '校验订座及出票时限，取最严格的限制'),
			2 =>array('caption' => '校验旅行时间取最严格的限制'),
			3 =>array('caption' => '折扣'),
			4 =>array('caption' => '变更与换开'),
			5 =>array('caption' => '因这个Category，常导致FSD显示金额与QTE产生的票面价有一定的价差'),
			6 =>array('caption' => ''),  // 有备注
			7 =>array('caption' => 'Category 17: HIP校验条件及EMA，egory 23: 混舱等杂项'),
			8 =>array('caption' => '生成小团队运价Small Group'),
     /*	10=>array('caption' => ''),  // 有备注
			11=>array('caption' => ''),  // 有备注*/
			9 =>array('caption' => '')   // 有备注
		);
		foreach ($arr as $title => $value) {
			$index = substr($title, 0,2);
			$row ++;
			if($index == '00' ){
				$array[0][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '01' || $index == '05' || $index == '15' || $index == '18' || $index == '35'){
				$array[1][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '02' || $index == '03' || $index == '04' || $index == '06' || $index == '07' || $index == '08' || $index == '09' || $index == '11' || $index == '14'){
				$array[2][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '19' || $index == '20' || $index == '21' || $index == '22'){
				$array[3][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '16' || $index == '31' ){
				$array[4][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '12' ){
				$array[5][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '10' ){
				$array[6][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '17' || $index == '23'){
				$array[7][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '13'){
				$array[8][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '25'){
				$array[9][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '33'){
				$array[10][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}
			if($index == '50'){
				$array[11][$index] = array('title'=>$title,'content'=>$value);
				continue;
			}

			$array[$row][$index] = array('title'=>$title,'content'=>$value);

			
		}
		// []行[][]列
		$array[1]['01']['lab'] = '（真实内容不可读）- 用来规定票价适用的条件，如Account Code，旅客类型';
		$array[1]['05']['lab'] = '用来规定提前订座和出票的限制';
		$array[1]['15']['lab'] = '（销售人限制部分的内容不可读）- 用来规定票价的可用时间、地区以及销售人';
		$array[1]['18']['lab'] = '（出票签注）- 票价签注栏信息';
		$array[1]['35']['lab'] = '（特殊私有运价，真实内容不可读）- 用来规定定向发布私有运价的发布范围、计价法则、代理费率、开票票证限制等';
		
		$array[2]['02']['lab'] = '用来规定票价使用相关的航班班期限制';
		$array[2]['03']['lab'] = '用来规定票价使用相关的季节性限制';
		$array[2]['04']['lab'] = '用来规定票价所适用或者不适用的航班限制';
		$array[2]['06']['lab'] = '最小停留限制，用来规定回程所允许的最早日期和指明计算最小停留时间段的基准点';
		$array[2]['07']['lab'] = '最长停留限制，用来规定回程所允许的最晚日期和指明计算最长停留时间段的基准点';
		$array[2]['08']['lab'] = '用来规定行程允许stopover(停留)的条件和收费';
		$array[2]['09']['lab'] = '（签转）- 用来规定行程允许Transfer(换乘)的条件和收费';
		$array[2]['11']['lab'] = '用来规定票价使用的除外时间';
		$array[2]['14']['lab'] = '用来规定航程中任意两点间的旅行时间限制';

		$array[3]['19']['lab'] = '（儿童婴儿的折扣）- 用来规定儿童、婴儿的折扣信息';
		$array[3]['20']['lab'] = '用来规定票价特定的旅行折扣';
		$array[3]['21']['lab'] = '用来规定票价特定的代理专享折扣';
		$array[3]['22']['lab'] = '用来规定票价特定的其他折扣';

		$array[4]['16']['lab'] = '用来规定票价的退改签政策';
		$array[4]['31']['lab'] = '用来规定自动变更/签转的规则数据';

		$array[5]['12']['lab'] = '用来规定票价中的附加费收取条件和费用';

		$array[6]['10']['lab'] = '（运价组合）- 用来规定票价/行程所允许的组合方式。';

		$array[7]['17']['lab'] = '较高点校验/里程制例外情况的设定';
		$array[7]['23']['lab'] = 'Add-on组合、多服务等级联运等';

		$array[8]['13']['lab'] = '用来规定陪伴旅行中陪伴者和被陪伴的类型和条件';

		$array[9]['25']['lab'] = '（真实内容不可读）- 特殊运价的规则';

		// $array[10]['33']['lab'] = '（功能正在开发中）- 用来规定自动退票的规则数据';

		// $array[11]['50']['lab'] = '用来规定票价规则的名称、适用地理区域、承运限制、规则所不适用的情况、行程类型和承运类型等其他信息，不影响票价计算';
		return $array;
	}

}