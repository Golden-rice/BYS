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
}