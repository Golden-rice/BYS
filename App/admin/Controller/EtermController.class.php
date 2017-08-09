<?php
namespace admin\Controller;
use BYS\Controller;
class EtermController extends Controller {
		// 前台展示
    public function xfsd(){
    	// import('vender/eterm/xfsd.command.php');
    	$this->display();
    }

    // 获取汇率
    public function toCNY(){
    	$xfsd = new XFSD($_SESSION['name'], $_SESSION['password'], $_SESSION['resource'], $rumtimeDir);
			$xfsd->command($_POST['command'],"w", false);
			$rate = $xfsd->changePrice();
			echo json_encode(array('rate'=>$rate));
    }

    // 通过输入框查询xfsd 
    public function searchByInput(){
			$start      = $_POST['start'];
			$endMore    = $_POST['end'];
			$startDate  = $_POST['startDate'];
			$aircompany = $_POST['aircompany'];
			$code       = $_POST['private'];
			$tripType   = $_POST['tripType'];
			$other      = $_POST['other'];

			if(preg_match("/[\/]2|[\/]2[\/]|2[\/]/",$other, $str)){
				$ab_flag = true;
			}
			
			$endArr  = explode(',', $endMore);

			$_SESSION['start'] = $start;
			$_SESSION['end'] = $endMore;  
			$_SESSION['startDate'] = $startDate;
			$_SESSION['aircompany'] = $aircompany;
			
			foreach($endArr as $end){
				$command = $this->toCommand($start, $end, $startDate, $aircompany, $tripType, $code, $other );

				if($remove){
					$xfsd->removeRuntime($command);
				}

				$xfsd->command($command, "w", false);

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
    public function searchByCommand(){
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

    public function toCommand( $start, $end, $startDate, $aircompany, $tripType, $code, $other ){
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
}