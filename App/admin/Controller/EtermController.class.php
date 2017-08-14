<?php
namespace admin\Controller;
use BYS\Controller;
class EtermController extends Controller {

		// xfsd 前台展示
    public function xfsd(){
    	import('vender/eterm/app.php');
    	$m = model('PrivateOwPolicy');
    	$m->prepare('SELECT * FROM e_ctrip_private_ow_policy WHERE id = 12140;');
    	// var_dump( $m->execute() );
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
			
			$endArr  = explode(',', $endMore);

			$_SESSION['start'] = $start;
			$_SESSION['end'] = $endMore;  
			$_SESSION['startDate'] = $startDate;
			$_SESSION['aircompany'] = $aircompany;
			
			foreach($endArr as $end){
				$command = $this->toXfsdCommand($start, $end, $startDate, $aircompany, $tripType, $code, $other );

				// if($remove){
				// 	$xfsd->removeRuntime($command);
				// }

				$xfsd->command($command, "w");

				if($ab_flag){
					$resultArr = $xfsd->analysis(array(1,2,3,4));
				}else{
					$resultArr = $xfsd->analysis(array(1,2,3));
				}
				
				$array[$end]=$resultArr;
				$array[$end]['from'] =  $code==''?'':$code;
				$array[$end]['aircompany'] = $aircompany;
				$array[$end]['startDate'] = $startDate;
				$array[$end]['length'] = count($resultArr);
				$array[$end]['command'] = $command;
			}
		

			echo json_encode(array('array'=>$array, 'time'=>'更新时间：'.date('Y-m-d H:i:s', $xfsd->fileTime)) );
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
				if(isset($_SESSION['data']) && count($_SESSION['data']) < 2  ){ // 只允许2个航段
					$_SESSION['data'][] = $_POST['data'];
				}
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

		public function showMixCabinTpl(){
			if($_GET['display'] == 1){
					$smarty->assign('nav', 'no');

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
									$array[] = matchRule($data_end_merge[$line], $data_end_merge[$line_match]);
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
										$array[] = matchRule($data[0][$end][$line], $data[0][$end][$line_match]);
								}
							}
						}	

					}else{
						echo 'ERROR: Not True Count of Session !';
					}

				if(isset($_GET['action']) && $_GET['action'] == 'byTpl'){
					if(isset($_POST['tpl']) && isset($_SESSION['data'])){
						$tpl = json_decode($_POST['tpl'], true);

						$tplName = $_POST['tplName'];
						$typeName = $_POST['typeName'];

						if( preg_match("/taobao/", $tplName) ){
							foreach ($array as $key => $value) {
								// $arrayByTpl[] = $array[$key];
								$tpl['outFileCode']   = "";
								$tpl['originLand']    = $array[$key]['start'];
								$tpl['destination']   = $array[$key]['end'];
								$tpl['cabin']         = $array[$key]['seat'];
								$tpl['FareBasis']      = $array[$key]['fare'];
								$tpl['flightDateRestrict4Dep']   = $array[$key]['allowWeek_1'];
								$tpl['flightDateRestrict4Ret']   = $array[$key]['allowWeek_2'];
								$tpl['minStay']       = $array[$key]['minStay'];
								$tpl['maxStay']       = $array[$key]['maxStay'];
								$tpl['ticketPrice']   = $array[$key]['backLineFee'] != ''? $array[$key]['backLineFee'] : $array[$key]['singleLineFee'];  
								$tpl['childPrice']    = $tpl['ticketPrice'];
								$arrayByTpl[] = $tpl;
								
							}
						}else if( preg_match("/xiecheng/", $tplName) ){
							foreach ($array as $key => $value) {
								$tpl['outFileCode']    = "";
								$tpl['DepartCity']     = $array[$key]['start'];
								$tpl['ArriveCity']     = $array[$key]['end'];
								// Routing 航路
								preg_match_all("/(\w)\,(\w)/",$array[$key]['seat'], $s);
								$seat = $s[1][0] == $s[2][0] ? $s[1][0]: $array[$key]['seat'];
								$tpl['RoutingClass']   = $seat;
								$tpl['FareBasis']      = $array[$key]['fare'];
								$tpl['OutboundDayTime']= $array[$key]['allowWeek_1'];
								$tpl['InboundDayTime'] = $array[$key]['allowWeek_2'];
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

						$smarty->assign('data', json_encode($arrayByTpl));
						$smarty->assign('tplMatch', json_encode(array('tplName'=>$tplName, 'typeName'=>$typeName)));
					}

					$smarty->display('admin/mixCabinByTpl.html');

				}else{
					$smarty->assign('data', json_encode($array));
					$smarty->assign('org_data', json_encode($data));

					$smarty->display('admin/mixCabin.html');
				}
				// echo '<pre>';
				// print_r( $data_end_merge );
				// echo '</pre>';

				// 垃圾回收
				// unset($_SESSION['data']);
			}			
		}
}