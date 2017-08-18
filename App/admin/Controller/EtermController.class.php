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

    	// import('vender/eterm/app.php');
    	// $m = model('PrivateOwPolicy');
    	// $m->prepare('SELECT * FROM e_ctrip_private_ow_policy WHERE id = 12140;');
    	// var_dump( $m->execute() );

    // 获取汇率
    public function toCNY(){
    	import('vender/eterm/app.php');

    	$xfsd = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
			$xfsd->command($_POST['command'],"w", false);
			$rate = $xfsd->changePrice();
			echo json_encode(array('rate'=>$rate));
    }

    // 通过输入框查询xfsd 
    public function searchXfsdByInput(){
    	import('vender/eterm/app.php');

    	$xfsd = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);

			$start      = $_POST['start'];
			$endMore    = $_POST['end'];
			$startDate  = $_POST['startDate'];
			$aircompany = $_POST['aircompany'];
			$code       = $_POST['private'];
			$tripType   = $_POST['tripType'];
			$other      = $_POST['other'];

			if(preg_match("/[\/]2|[\/]2[\/]|2[\/]/",$other, $str)){
				$ab_flag = true;
			}else{
				$ab_flag = false;
			}
			
			$endArr  = explode(',', $endMore);  // 多地点录入时
			$array   = array();                 // 解析结果的数组，支持多个地点
			
			foreach($endArr as $end){

				// 清空
				$command = $this->toXfsdCommand($start, $end, $startDate, $aircompany, $tripType, $code, $other );

				// 开始查询
				$xfsd->command($command, "w");

				$source = $this->hasXfsdSource(array('command' => $command, 'firstPage' => $xfsd->getFirstPage() ));

				if( $source ){ 
				// 查询旧source数据作为tmp
					$xfsd->wtTmp($source);
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
				$array[$end]['from'] = $code==''?'':$code;
				$array[$end]['aircompany'] = $aircompany;
				$array[$end]['startDate'] = $startDate;
				$array[$end]['length'] = count($resultArr);
				$array[$end]['command'] = $command;

			}

			// 保存解析结果
			$this->saveXfsdResult($array);

			echo json_encode(array('array'=>$array, 'time'=>'更新时间：'.date('Y-m-d H:i:s', $xfsd->fileTime)) );
    }

    // 储存xfsd source资源
    public function saveXfsdSource($array = array()){

    	$m_xfsd = model('xfsd_source');
    	$add = array(
    		'office' => $_SESSION['resource'],
    		'status' => 2,
    		'command' => isset($array['command'])? $array['command'] : '',
    		'detail' => isset($array['source'])? $array['source']: '',
    	);
    	return $m_xfsd->add($add);
    }

    // 储存xfsd 解析结果，用是否含id来区分是否保存
    public function saveXfsdResult($array){
    	if( count($array) == 0 ) return;

    	$m_xfsd = model('xfsd_result');
    	$addAll = array();

    	foreach ($array as $end => $list) {
    		if(!isset($list['id'])) continue;
    		for($i = 0; $i < count($list)-6; $i++){
    			$value = $list[$i];
		    	$addAll[] = array(
		    		//  fareKey 关键字：dep_city/arr_city/airline/pax_type/source/source_office/source_agreement//fare_date
						'FareKey'    => "{$value['start']}/{$value['end']}/{$list['aircompany']}/ADT/{$_SESSION['resource']}/{$list['from']}//".date('Ymd',strtotime($list['startDate'])), 
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
    public function hasXfsdSource($array = array()){
    	$m_xfsd = model('xfsd_source');
    	$result = $m_xfsd ->where('`command` ="'.$array['command'].'" ')->select();

    	// 为空时
    	if(count($result) == 0) return false;

    	foreach ($result as $rows => $cols) {
    		if(isset($array['firstPage']) && $flength = strlen($array['firstPage'])  ){
    			if ( $array['firstPage'] == substr($cols['Detail'], 0 , $flength) ) 
    				return $cols['Detail'];
    			else
    				return false;
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
			if(preg_match_all('/<(SD|CH|IN|ADT|ZZ)/', $command, $str)){
				$str = $str[1][0];
				// $smarty->assign('identity', $str); 未增加
			}

			$str = preg_match_all('/(\/\/\/)#(\w+\*?\w+)/',substr($command, 19), $arr);
			// $smarty->assign('from', $str ? $arr[2][0]:'公布运价'); 未增加

			if($remove){
				$xfsd->removeRuntime($command);
			}

			$xfsd->command($command, "w", false);
			$resultArr = $xfsd->analysis(array(1,2,3));
			$array = array(
				"OWEND" => $resultArr
				);

			$array["OWEND"]['from'] =  $code==''?'公布运价':$code;
			$array["OWEND"]['aircompany'] = $aircompany;
			$array["OWEND"]['startDate'] = $startDate;
			$array["OWEND"]['length'] = count($resultArr);
			$array["OWEND"]['command'] = $command;
    }

    public function toXfsdCommand( $start, $end, $startDate, $aircompany, $tripType, $code, $other ){
			// NUC 数值
			$other .= "/NUC";

			// 根据出发到达组合成合适的命令
			if(!empty($tripType)){
				$tripType = '/'.$tripType;
			}
			if($code){
				return $command = 'XS/FSD'.$start.$end.'/'.$startDate.'/'.$aircompany.$tripType.'/NEGO/X///#'.$code.'/'.$other;
			}else{
				return $command = 'XS/FSD'.$start.$end.'/'.$startDate.'/'.$aircompany.$tripType.'/X'.'/'.$other ;
			}	
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
				// $startTime  = $_POST['startTime'];        
				// $airCommon  = $_POST["airCommon"]; 

				// 多目的地
				if(preg_match("/,/", $end)){
					$endArr = explode(",", $end);
				}

				// 多出发地
				if(preg_match("/,/", $start)){
					$startArr = explode(",", $start);
				}

				// 删除缓存
				if(isset($_POST['remove']) && $_POST['remove'] == 1){
					$remove = ture;
				}

				if($other){
					$other= '/'.$other;
				}

		 		if(isset($startTime)){
					$startTime= '/'.$startTime;
				}else{
					$startTime = "";
				}

				$during = (strtotime($endDate)-strtotime($startDate))/(24*60*60);
				$array = array();

					if(isset($endArr)){

						foreach ($endArr as  $value) {
							$array[$value] = array();
							
							for ($i=0; $i <= $during; $i++) { 
								$days = strtotime($startDate) +$i*24*60*60;
								$m = strtoupper(date('M',$days));
								$d = strtoupper(date('d',$days));
								$date = $d.$m;
								$command = 'AVH/'.$start.$value.$date.$startTime.$other.'/'.$airCompany;

								if($remove){
									$avh->removeRuntime($command);
								}
								
								$avh->command($command, "w", false);	
								$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
							}
						}
								echo json_encode(array('array'=>$array, "type"=>"array")) ; // 'time'=>'更新时间：'.date('Y-m-d H:i:s', $avh->fileTime)

					}else if(isset($startArr)){
						foreach ($startArr as  $value) {
							$array[$value] = array();
							
							for ($i=0; $i <= $during; $i++) { 
								$days = strtotime($startDate) +$i*24*60*60;
								$m = strtoupper(date('M',$days));
								$d = strtoupper(date('d',$days));
								$date = $d.$m;
								$command = 'AVH/'.$value.$end.$date.$startTime.$other.'/'.$airCompany;

								if($remove){
									$avh->removeRuntime($command);
								}
								
								$avh->command($command, "w", false);	
								$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
							}
						}
								echo json_encode(array('array'=>$array, "type"=>"array")) ; // 'time'=>'更新时间：'.date('Y-m-d H:i:s', $avh->fileTime)

					}else{
						for ($i=0; $i <= $during; $i++) { 
							$days = strtotime($startDate) +$i*24*60*60;
							$m = strtoupper(date('M',$days));
							$d = strtoupper(date('d',$days));
							$date = $d.$m;
							$command = 'AVH/'.$start.$end.$date.$startTime.$other.'/'.$airCompany;

							if(isset($remove)){
								$avh->removeRuntime($command);
							}
						
							$avh->command($command, "w", false);	
							$array = array_merge($array, $avh->analysis(array(1,2,6)));
						}
						// ob_flush(); // 刷新缓存
								echo json_encode(array('array'=>$array,"type"=>"single")) ; // 'time'=>'更新时间：'.date('Y-m-d H:i:s', $avh->fileTime)

					}
			}	

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

}