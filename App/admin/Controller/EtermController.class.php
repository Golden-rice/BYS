<?php
namespace admin\Controller;
use BYS\Controller;
class EtermController extends Controller {

	private $cache = array(); // 临时储存

	// xfsd 运价 前台展示
  public function xfsd(){
  	$this->display();
  }

  // avh 舱位 前台展示
  public function avh(){
		$this->display();
  }

  // fare使用规则 前台展示
  public function fare(){
  	$this->display();
  }

  // fsl 航程 前台展示
  public function routing(){
  	$this->display();
  }

	// ------------------------ COMMON ------------------------

  // 获取汇率
  public function toCNY(){
  	import('vender/eterm/app.php');

  	$xfsd = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
		$xfsd->command($_POST['command'],"w", false);
		$rate = $xfsd->changePrice();
		echo json_encode(array('rate'=>$rate));
  }

  // 查询 cmd 的source 是否存在
  private function hasCmdSource($where = array(), $cmd = ''){
  	$m = model("{$cmd}_source");
  	$result = $m->find($where);

  	// 为空返回false
  	if(!$result || empty($result[0]) ) return false;
  	$col = $result[0]; // 仅一条
		if ( isset($col['Command']) )  // 是否获取正确数据
			if( $where['office'] == $col['Office']  && $where['command'] == $col['Command'] )
				return $col;

  	return false;
  }

  // 存储 cmd 的source 
  private function saveCmdSource($array = array(), $cmd=''){
  	$m = model("{$cmd}_source");
  	$add = array(
  		'office'  => $_SESSION['resource'],
  		'status'  => 2,
  		'command' => isset($array['command'])? $array['command'] : '',
  		'detail'  => isset($array['source'])? $array['source']: '',
  	);
  	$result = $m->add($add);
  	return $result;
  }

  // 更新 cmd 的source 
  private function updateCmdSource($array = array(), $cmd = ''){
		$m = model("{$cmd}_source");
		if(isset($array['source'])){
	  	$update = array(
	  		'detail' => $array['source'],
	  		'GmtModified' => time()
	  	);
		}else{
	  	$update = array(
	  		'GmtModified' => time()
	  	);
		}
  	$m->where('`command` = "'.$array['command'].'" ')->update($update);
  	// 仅有一条
  	$result = $m->where('`command` = "'.$array['command'].'" ')->select();
  	return $result[0]['Id'];
	}

	// 更新 cmd 的result
	private function updateCmdResult($array = array(), $id = NULL, $command = '', $cmd = 'test'){
		if( count($array) == 0 ) return;

		$this->deleteCmdResult($id, $cmd);
		$saveActionName = 'save'.ucfirst($cmd).'Result';
		$this->$saveActionName($array, $id, $command); 
	}

	// 测试数据
	private function saveTestResult($array, $id, $command){
		echo 'test save name';
	}

	// 删除 cmd 的result
	private function deleteCmdResult($id, $cmd = ''){
		if(!$id) return;
		$m = model("{$cmd}_result");
		$result = $m->where("`sid` = {$id}")->delete();
	}

	// 根据sid查询 cmd 的 result
	private function searchCmdResult($sid, $cmd = ''){
		if($cmd == '') return;
		$m = model("{$cmd}_result");

		return $m->where("`sid` = {$sid}")->select();
	}

	// ------------------------ XFSD ------------------------

  public function searchXfsdResult(){
  	$result = $this->searchCmdResult($_POST['sid'], 'xfsd');
  	echo json_encode(array('result'=>$result));
  }

  // 通过输入框查询xfsd 
  public function searchXfsdByInput($return = false){
  	import('vender/eterm/app.php');

  	if(isset($_POST['username'])){
  		$_SESSION['name']      = $_POST['username'];
  		$_SESSION['password']  = $_POST['password'];
  		$_SESSION['resource']  = $_POST['resource'];
  	}

		$start      = $_POST['start'];
		$endMore    = $_POST['end'];
		$startDate  = $_POST['startDate'];
		$aircompany = $_POST['aircompany'];
		$code       = $_POST['private'];
		$tripType   = $_POST['tripType'] == '' ? '':'*'.ltrim($_POST['tripType'], '*');
		$other      = $_POST['other'];
		$ab_flag    = preg_match("/[\/]2|[\/]2[\/]|2[\/]/",$other, $str) ? true:false;
		$endArr     = explode(',', rtrim($endMore,','));  // 多地点录入时
		$array      = array();                            // 解析结果的数组，支持多个地点
		$query      = array(                              // 查询变量
			'start'     => $_POST['start'],
			'end'       => $_POST['end'],
			'startDate' => $_POST['startDate'],
			'aircompany'=> $_POST['aircompany'],
			'code'      => $_POST['private'],
			'tripType'  => $_POST['tripType'],
			'other'     => $_POST['other']
		);
  	$xfsd       = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource'], $query);
		
		foreach($endArr as $end){
			// 生成命令
			$command = strtoupper($this->toXfsdCommand($start, $end, $startDate, $aircompany, $tripType, $code, $other ));
			$result  = $this->hasCmdSource(array('command'=>$command, 'office'=>$_SESSION['resource']), 'xfsd');
			// 有，日期已过期
			if ( isset($result['GmtModified']) && $result['GmtModified'] + 24*60*60 < time() ){ 

				$xfsd->command($command, "w");
				// 检查第一页是否与原数据相同，如果相同不过期，如果不同则更新
				$firstPage = $xfsd->getFirstPage();

				if(is_string($firstPage) && $flength = strlen($firstPage) ){
					// 第一页数据不同，更新 source 及 result
	  			if ( !isset($result['Detail']) || $firstPage != substr($result['Detail'], 0 , $flength) ) {
	  				// 更新 source
						$id          = $this->updateCmdSource(array('source' => $xfsd->readSource(), 'command' => $command), 'xfsd');
						$resultArr   = $ab_flag ? $xfsd->analysis(array(2,3,4)) : $xfsd->analysis(array(2,3));
						$array[$end] = $resultArr;
						// 更新 result
						$this->updateCmdResult($array[$end], $id, $command, 'xfsd'); 
	  			}
  				// 第一页数据相同，读取数据库
	  			else{
	  				$xfsd->wtTmp($result['Detail']);
	  				$id          = $result['Id'];
	  				$resultArr   = $ab_flag ? $xfsd->analysis(array(2,3,4)) : $xfsd->analysis(array(2,3));
						$array[$end] = $resultArr;
						// 更新 source GmtModified
						$this->updateCmdSource(array('GmtModified' => time(), 'command' => $command), 'xfsd');
	  			}
	  		}else{
	  			echo '第一页的数据检查出错';
	  			return;
	  		}
	  		
			}
			// 有，但是储存时间不大于一天，读取数据库数据
			elseif( isset($result['GmtModified']) && $result['GmtModified'] + 24*60*60 >= time()){

				$id = $result['Id'];
				$xfsd->wtTmp($result['Detail']);
				$resultArr   = $ab_flag ? $xfsd->analysis(array(2,3,4)) : $xfsd->analysis(array(2,3));
				$array[$end] = $resultArr;
			}
			// 无，从新查询，并临时保存 source 及 result 
			else{
				
				$xfsd->command($command, "w");
				$resultArr   = $ab_flag ? $xfsd->analysis(array(1,2,3,4)) : $xfsd->analysis(array(1,2,3));
				$array[$end] = $resultArr;
				$id          = $this->saveCmdSource(array('source' => $xfsd->readSource(), 'command' => $command), 'xfsd'); // 储存至数据库
				$this->saveXfsdResult($array[$end], $id, $command);

			}

			// 封装基础数据格式，仅用于前台展示用
			$array[$end]['from']       = $code==''?'':$code;
			$array[$end]['aircompany'] = $aircompany;
			$array[$end]['startDate']  = $startDate;
			$array[$end]['length']     = count($resultArr);
			$array[$end]['command']    = $command;
			$array[$end]['other']      = $other;
			$array[$end]['id']         = $id;

			ob_flush();
			flush();
		}

		if($return){
			if(!isset($id)) $id = NULL; // 返回储存和更新的sid
			return array('array'=>$array, 'msg'=> \BYS\Report::printLog(), 'id'=>$id);
		}else{
			echo json_encode(array('array'=>$array, 'time'=>'更新时间：'.date('Y-m-d H:i:s', $xfsd->fileTime), 'id'=>$id));
		}
  }

  // 储存xfsd 解析结果，用是否含id来区分是否保存
  private function saveXfsdResult($array, $id = NULL, $command = ''){
  	if( count($array) == 0 ) return;

		if( $id == NULL) return;

  	$m_xfsd = model('xfsd_result');
  	$addAll = array();
  	foreach ($array as $num => $value) {
    	$addAll[] = array(
    		//  fareKey 关键字：dep_city/arr_city/airline/pax_type/source/source_office/source_agreement/other(其他字段)/fare_date
				'FareKey'        => "{$value['start']}/{$value['end']}/{$value['aircompany']}/ADT/{$_SESSION['resource']}/{$value['from']}/{$value['other']}/".date('Ymd',strtotime($value['startDate'])), 
				// 命令
				'Command'        => $command,
				// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
				'Status'         => 2,
				// OFFICE 号
				'Office'         => $_SESSION['resource'],
				// source id
				'Sid'            => $id,
				// fare FareBasis
				'FareBasis'      => $value['fare'],
				// special 特殊规则
				'xfsd_Special'   => $value['special'],
				// advp 提前出票
				'xfsd_Advp'      => $value['ADVPDay'],
				// allowDateStart 适用日期起始
				'xfsd_DateStart' => empty($value['allowDateStart'])? '1970-01-01' : date('Y-m-d',strtotime($value['allowDateStart'])),
				// allowDateEnd 适用日期结束
				'xfsd_DateEnd'   => empty($value['allowDateEnd'])  ? '2099-12-31' : date('Y-m-d',strtotime($value['allowDateEnd'])),
				// backLineFee 往返费用
				'xfsd_RoundFee'  => $value['backLineFee'],
				// singleLineFee 单程费用
				'xfsd_SingleFee' => $value['singleLineFee'],
				// start 出发
				'xfsd_Dep'       => $value['start'],
				// end 到达
				'xfsd_Arr'       => $value['end'],
				// aircompany 航空公司
				'xfsd_Owner'     => $value['aircompany'],
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
			 	// reTicket
			 	'xfsd_Rule'      => $value['reTicket'],
    	);
  	}
  	$m_xfsd->addAll($addAll);
  }

  // 通过command查询xfsd
  public function searchXfsdByCommand(){
  	import('vender/eterm/app.php');
  	$xfsd = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);

  	$command = strtoupper($_POST['command']);

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

// ------------------------ AVH ------------------------

	// 通过输入框查询avh 
	// $return 是打印数据还是 返回数据
	public function searchAvhByInput($return = false){
		import('vender/eterm/app.php');
		$avh = new \Avh($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);

		// 读取avh数据
		$start      = $_POST['start']; 
		$end        = $_POST['end'];
		$startDate  = $_POST['startDate'];        
		$endDate    = $_POST['endDate']; 
		$aircompany = $_POST['aircompany'];   
		$other      = $_POST['other'];    

	 	// 航空公司前缀处理
	 	if(!preg_match("/^(\/|\*)\w{2}$/", $aircompany, $prefix))
	 		$aircompany = '/'.$aircompany;
	 	
		// 多目的地
		if(preg_match("/,/", $end))
			$endArr   = explode(",", $end);

		// 多出发地
		if(preg_match("/,/", $start))
			$startArr = explode(",", $start);

		// 其他参数
		if($other)
			$other    = '/'.$other;

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
			$idArray = array();
			// 拼装命令及执行
			for ($i = 0; $i <= $during; $i++) { 
				$days    = strtotime($startDate) +$i*24*60*60;
				$m       = strtoupper(date('M',$days));
				$d       = strtoupper(date('d',$days));
				$date    = $d.$m;
				// O : 承运航班
				// E : 出发时间排序（DESC）
				// A : 到达时间排序（DESC）
				$command = strtoupper($repeat['pos'] == 'start' ? 'AVHOE/'.$value.$end.$date.$other.$aircompany : 'AVHOE/'.$start.$value.$date.$other.$aircompany);
				$result  = $this->hasCmdSource(array('command'=>$command, 'office'=>$_SESSION['resource']), 'avh');
				if ( isset($result['GmtModified']) && $result['GmtModified'] + 24*60*60 < time() ){ 
					// 有且存储时间大于一天，更新
					$avh->command($command, "w", false);
					$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
					$id = $this->updateCmdSource(array('source' => $avh->readSource(), 'command' => $command),'avh');
					array_push($idArray, $id);
					// 更新result
					$this->updateCmdResult($array[$value], $id, $command,'avh');
				}
				elseif( isset($result['GmtModified']) && $result['GmtModified'] + 24*60*60 >= time()){
					// 有，但是储存时间不大于一天

					if($result['Detail'] === ''){
						// 如果没有返回数据，更新
						$avh->command($command, "w", false);
						$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
						$id = $this->updateCmdSource(array('source' => $avh->readSource(), 'command' => $command),'avh');
						array_push($idArray, $id);
						// 更新result
						$this->updateCmdResult($array[$value], $id, $command,'avh');
					}else{
						// 读取数据库数据
						$id = $result['Id'];
						array_push($idArray, $id);
						$avh->wtTmp($result['Detail']);
						$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
						// 更新 source GmtModified
						$this->updateCmdSource(array('GmtModified' => time(), 'command' => $command), 'avh');
					}
				}
				else{
					// 没有，新增
					$avh->command($command, "w", false);
					$array[$value] = array_merge($array[$value], $avh->analysis(array(1,2,6)));
					// 此处返回的id不准确，会被其他日期最新的id替换掉
					$id = $this->saveCmdSource(array('source' => $avh->readSource(), 'command' => $command), 'avh'); // 储存至数据库
					array_push($idArray, $id);
					$this->saveAvhResult($array[$value], $id, $command);
				}
			}
		}

		if($return){
			if(!isset($id)) $id = NULL; // 返回储存和更新的sid
			return array('array'=>$array, 'msg'=> \BYS\Report::printLog(), 'id'=>$idArray);
		}
		else{
			echo json_encode(array('array'=>$array, "type"=>'array'));
		}
	}

	// 储存 avh_result
	public function saveAvhResult($array = array(), $id = NULL, $command = ''){
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
						'avh_DepTime'   => date('Y-m-d',time()).' '.$value['startTime'],
						// endTime 到达时间
						'avh_ArrTime'   => ($value['endTime'][6] == '+'? date('Y-m-d', strtotime("+1 day")) : date('Y-m-d',time())).' '.substr($value['endTime'], 0, 5),
						// 飞行时间
						'avh_FlightTime'=> $value['flightTime'],
						// startDate 出发日期
						'avh_Date'      => date('Y-m-d',strtotime($value['startDate'])),
						// flight 航班号
						'avh_Flight'    => $value['flight'],
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

  public function searchAvhResult(){
  	$result = $this->searchCmdResult($_POST['sid'], 'avh');
  	echo json_encode(array('result'=>$result));
  }

	// ------------------------ FSL ------------------------

	public function searchFslByInput($return = false){
		import('vender/eterm/app.php');

		$aircompany = $_POST['aircompany'];
		$start      = $_POST['start'];
		$end        = $_POST['end'];
		$array      = array();   // 最终航程结果

		$command    = 'XS/FSD'.$start.$end.'/'.date('tM', time()+10*24*60*60 ).'/'.$aircompany.'/NEGO/X';
		$fsl        = new \Fsl($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);

		$result = $this->hasCmdSource(array('command'=>$command, 'office'=>$_SESSION['resource']), 'fsl');
		// 比default多个analysis 
		if ( isset($result['GmtModified']) && $result['GmtModified'] + 30*24*60*60 > time() ){ 
			$id = $result['Id'];
			$fsl -> wtTmp($result['Detail']);
			$fsl_result = $fsl -> analysis(array(3,4), array('aircompany'=>$aircompany)); 

			if(!empty($fsl_result)){
				$array["{$start}{$end}/{$aircompany}"] = $fsl_result;
				$array["{$start}{$end}/{$aircompany}"]['length'] = count($array["{$start}{$end}/{$aircompany}"])-1;
			}
				
		}else{
			$fsl -> command($command, 'w');
			$fsl_result = $fsl -> analysis(array(1,2,3,4), array('aircompany'=>$aircompany));
			if(!empty($fsl_result)){
				$array["{$start}{$end}/{$aircompany}"] = $fsl_result;
				$array["{$start}{$end}/{$aircompany}"]['length'] = count($array["{$start}{$end}/{$aircompany}"])-1;
			}			
			$id = $this-> saveCmdSource(array('source' => $fsl->readSource(), 'command' => $command), 'fsl');
		}

		if($return){
			if(!isset($id)) $id = NULL; // 返回储存和更新的sid
			return array('array'=>$array, 'msg'=> \BYS\Report::printLog(), 'id'=>$id);
		}
		else
			echo json_encode(array('array'=>$array, 'msg'=> \BYS\Report::printLog()));
	}

	public function searchFslByDefault(){
		import('vender/eterm/app.php');
		// ** 
		$depart   = array(
			'UA' => array( 'BJS' ),
			'DL' => array( 'BJS' )
		);
		$arrive   = array( 
			'UA' => array('SFO', 'IAD', 'BOS', 'LAX', 'EWR', 'ORD', 'MCO', 'IAH', 'SLC', 'DEN', 'CVG', 'LAS', 'PIA', 'ATL', 'RDU', 'BDL', 'BTV', 'SEA', 'SAN', 'CLT', 'BNA', 'DFW', 'PIT', 'DSM', 'CLE', 'BOI', 'MIA', 'SGF', 'STL', 'ROA', 'YYZ', 'PHL', 'OMA', 'BUF', 'PDX', 'MSP', 'OKC', 'HSV', 'PHX', 'IND', 'DCA', 'SBN', 'LGA', 'SAT', 'CMH', 'LAN', 'MCI', 'JAC', 'CMI', 'EUG', 'AUS', 'MEX', 'SYR', 'FLL', 'GEG', 'DTW', 'ALB', 'RIC', 'YUL'),
			'DL' => array('SEA', 'DTW', 'BOS', 'LAX', 'MCO', 'SLC', 'ATL', 'DEN' )
		);
		$arrive   = array( 
			'UA' => array('SFO', 'IAD'),
			'DL' => array('SEA', 'DTW')
		);

		// 航程
		$array = array();
		foreach($depart as $aircompany => $d){
			foreach ($arrive[$aircompany] as $end) {
		// 		echo $d[0].'-'.$end."<br>";
				$start      = $d[0];
				$command    = 'XS/FSD'.$start.$end.'/'.date('tM', time()+10*24*60*60 ).'/'.$aircompany.'/NEGO/X';
				$fsl        = new \Fsl($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
				$result     = $this->hasCmdSource(array('command'=>$command, 'office'=>$_SESSION['resource']), 'fsl');

				// 数据保留1个月
				if ( isset($result['GmtModified']) && $result['GmtModified'] + 30*24*60*60 > time() ){ 
					$fsl -> wtTmp($result['Detail']);
					$array["{$start}{$end}/{$aircompany}"] = $fsl -> analysis(array(3,4), array('aircompany'=>$aircompany));
				}else{
					$fsl -> command($command, 'w');
					$array["{$start}{$end}/{$aircompany}"] = $fsl -> analysis(array(1,2,3,4), array('aircompany'=>$aircompany));
					$this-> saveFslSource(array('source' => $fsl->readSource(), 'command' => $command));
					sleep(3);
				}
				ob_flush();
				flush();
			}
		}


		\BYS\Report::p($array);
	}

	// ------------------------ SK ------------------------

	// 查询航班时刻
	public function searchSkByInput($return = false){
		import('vender/eterm/app.php');
		$sk       = new \Sk($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
		$config   = array('start'=>$_POST['start'], 'end'=>$_POST['end'], 'aircompany'=>$_POST['aircompany']);

		// $command  = "SK:/{$config['start']}{$config['end']}/{$config['aircompany']}";
		$command  = $sk->set($config)->rtCommand();
		$result   = $this->hasCmdSource(array('command'=>$command, 'office'=>$_SESSION['resource']), 'sk');

		// 有，根据result 中的 date 日期已过期
		if ( $result ){ 

			$id            = $result['Id'];
			$result_result = $this->searchCmdResult($id, 'sk');
			$switchNew     = false;

			foreach ($result_result as $rKey => $rVal) {
				// result 中的 date 日期已过期
				if($rVal['Sk_AllowEndDate'] !== "2099-12-31" && time($rVal['Sk_AllowEndDate']) < TIME ){
					$switchNew = true;
					break;
				}
			}
			// 有，但是日期过期
			if($switchNew){
				$result_eterm = $sk->run()->set($config)->parseDetail();
				// 更新 source 
				$this->updateCmdSource(array('GmtModified' => time(), 'command' => $command), 'sk');
				// 更新 result
				$this->updateCmdResult($result_eterm, $id, $command, 'sk'); 
			}
			// 有，日期没过期，从数据库中取
			else{
				$sk->wtTmp($result['Detail']);
				$result_eterm = $sk->parseDetail($config);
			}
			
		}
		// 无，从新查询，并临时保存 source 及 result 
		else{
			$result_eterm = $sk->run()->parseDetail();
			$id           = $this->saveCmdSource(array('source' => preg_replace("/(')/", "\\\\$1", $sk->rtTmp()), 'command' => $command), 'sk'); // 储存至数据库
			$this->saveSkResult($result_eterm, $id, $command);
		}

		if($return){
			if(!isset($id)) $id = NULL; // 返回储存和更新的sid
			return array('array'=>$result_eterm, 'msg'=> \BYS\Report::printLog(), 'id'=>$id);
		}else{
			echo json_encode(array('array'=>$result_eterm,  'id'=>$id));
		}
	}

	public function saveSkResult($array = array(), $id = NULL, $command = ''){
  	if( count($array) == 0 ) return;

  	$m      = model('sk_result');
  	$addAll = array();
  	// 航路
  	foreach ($array as $rkey => $rVal) {
  		// 航段
  		foreach ($rVal as $key => $value) {
  			// 作用点
  			if(substr($value['allowWeek'],0,1) === 'X' ){
	  			$allWeek   = array(1,2,3,4,5,6,7);
	  			foreach ($allWeek as $day) {
	  				if(!preg_match("/{$day}/", $value['allowWeek'])){
	  					$allowWeek .= $day;
	  				}
	  			}
  			}else{
  				$allowWeek = $value['allowWeek'];
  			}

  			$addAll[]=array(
					// 命令
					'Command'       => $command,
					// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
					'Status'        => 2,
					// OFFICE 号
					'Office'        => $_SESSION['resource'],
					// source id
					'Sid'           => $id,
					// 航班号
					'Sk_Flight'     => $value['flight'], 
					// 出发
					'Sk_Dep'        => $value['start'],
					// 到达
					'Sk_Arr'        => $value['end'],
					// 航空公司
					'Sk_Aircompany' => $value['aircompany'],
					// 出发时间
					'Sk_DepTime'    => $value['startTime'],
					// 到达时间
					'Sk_ArrTime'    => $value['endTime'],
					// 是否直达
				  'Sk_IsDirect'   => $value['haveStay'],
				  // 分组 Id-rkey-index
				  'Sk_Rid'        => "{$id}-{$rkey}-{$key}",
				  // 作用点，将X转换成可用
				  'Sk_AllowWeek'  => $allowWeek,
				  // 起始适用日期
				  'Sk_AllowStartDate'  => date('Y-m-d',strtotime($value['startDate'])),
				  // 结束适用日期
				  'Sk_AllowEndDate'    => empty($value['endDate'])  ? '2099-12-31' : date('Y-m-d',strtotime($value['endDate'])),
				  // 未知
				  'Sk_Other'      => $value['other'],
  			);
  		}
  	}

  	$m->addAll($addAll);
	}

	// ------------------------ YY ------------------------
	// 设置基础YY 
  public function setYyArea($return = false){
  	// 按照区域跑，省去只跑某航空公司
    $_POST['start'] = 'CN';
    $_POST['end']   = 'US';
    // $_POST['aircompany'] = 'UA';
    $this->searchYyByInput($return);
  }

  public function test(){
    $_POST['aircompany'] = 'UA';
    $this->searchRoutingByOneStay();
  }


  // 查询某航空公司下一次中转的目的地
  public function searchRoutingByOneStay(){
  	$m_result   = model('yy_result');
  	// $result_end = $m_result->distinct('Yy_End AS end, Yy_End_Input AS end_area')->where("Yy_Aircompany='{$_POST['aircompany']}' AND Yy_IsCommon=0 AND Yy_Start_Input = 'CN'")->select();
  	$result_end = $m_result->where("Yy_Aircompany='{$_POST['aircompany']}' AND Yy_IsCommon=0 AND Yy_Start_Input = 'CN'")->select();

  	if($result_end){
  		// 将中转点作为出发地
  		$endArray = array();
  		foreach ($result_end as $eKey => $eVal) {
  			if(!in_array($eVal['Yy_End'], $endArray)) array_push($endArray, $eVal['Yy_End']);
  		}

  		// 批量获得中转城市
  		$_POST['aircompany'] = $result_end[0]['Yy_End_Input'];
  		$result_stay = array();
  		foreach ($endArray as $end) {
  			$_POST['start'] = $end;
  			$result_stay[$end] = $this->searchYyByInput(true);
  			ob_flush();
  			flush();
  		}
  		echo json_encode(array('msg'=>\BYS\Report::printLog(), 'result_direct'=>$result_end, 'result_stay'=>$result_stay));

  	}else{
  		echo '没有该航空公司数据';
  		var_dump($result_end, $m_result->testSql());
  	}
  }

	// 查询航班时刻
	public function searchYyByInput($return = false){
		import('vender/eterm/app.php');
		$yy      = new \Yy($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
		$config  = array('start'=>$_POST['start']); 

		$command = $yy->set($config)->rtCommand();
		$result  = $this->hasCmdSource(array('command'=>$command, 'office'=>$_SESSION['resource']), 'yy');

		// 有，根据result 中的 date 日期已过期
		if ( $result ){ 
			$id = $result['Id'];
			$yy->wtTmp($result['Detail']);
			$result_eterm = $yy->parseDetail($config);
		}
		// 无，从新查询，并临时保存 source 及 result 
		else{
			// 查询出发到达 返回结果是 出发到达
			$result_eterm = $yy->run()->getToEnd()->parseDetail();
			$id           = $this->saveCmdSource(array('source' =>  $yy->rtTmp(), 'command' => $result_eterm['command']), 'yy'); // 储存至数据库
			$this->saveYyResult($result_eterm['result'], $id, $result_eterm['command']);
		}

		if($return){
			if(!isset($id)) $id = NULL; // 返回储存和更新的sid
			return array('array'=>$result_eterm['result'], 'msg'=> \BYS\Report::printLog(), 'id'=>$id);
		}else{
			echo json_encode(array('array'=>$result_eterm['result'],  'id'=>$id));
		}
	}

	public function saveYyResult($array = array(), $id = NULL, $command = ''){
  	if( count($array) == 0 ) return;

  	$m      = model('yy_result');
  	$addAll = array();
  	if(preg_match('/[Yy]+\/(\w+)/', $command, $match) && isset($match[1])){
  		$startInput = '';
  		$endInput   = '';
  		if(strlen($match[1]) === 4){
  			$startInput = substr($match[1],0,2);
  			$endInput   = substr($match[1],2,2);
  		}elseif(strlen($match[1]) === 6){
  			$startInput = substr($match[1],0,3);
  			$endInput   = substr($match[1],3,3);
  		}else{
  			$startInput = $match[1];
  		}
  	}
  	foreach ($array as $rkey => $rVal) { // 不同的行程
  		foreach ($rVal as $key => $value) { // 行程
  			$addAll[] = array(
					// 命令
					'Command'       => $command,
					// 状态：-2 已知错误发生 -1 失败 0 等待 1 进行中 2 成功
					'Status'        => 2,
					// OFFICE 号
					'Office'        => $_SESSION['resource'],
					// source id
					'Sid'           => $id,
					// 出发区域
					'Yy_Start_Input'=> $startInput,
					// 到达区域
					'Yy_End_Input'  => $endInput,
					// 出发
					'Yy_Start'      => $value['start'],
					// 到达
					'Yy_End'        => $value['end'],
					// 航空公司
					'Yy_Aircompany' => $value['aircompany'],
					// 是否是共享
					'Yy_IsCommon'   => $value['isCommon'],
  			);
  		}
  	}
  	$m->addAll($addAll);
	}


	// ------------------------ 混舱 ------------------------

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

		if(empty($data)) 
			\BYS\Report::error('ERROR: No Session Data !');

		if(count($data) == 2){ // 最多2个航段
			// $data = json_decode($data, true);
			// 合并航段 (A+B)
			foreach ($data as $num => $value) {
				$data[$num] = json_decode($value, true);
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
			\BYS\Report::error('ERROR: Not True Count of Session !');
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

			$array    = $_SESSION['mixCabin'];
			$tplName  = $_POST['tplName'];
			$typeName = $_POST['typeName'];

			if( preg_match("/taobao/", $tplName) ){
				foreach ($array as $key => $value) {
					// 需要扩展已确认值
					// $arrayByTpl[] = $array[$key];
					$tpl['outFileCode']   = "";
					$tpl['originLand']    = $value['start'];
					$tpl['destination']   = $value['end'];
					$tpl['cabin']         = $value['seat'];
					$tpl['FareBasis']     = $value['fare'];
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
					// 舱位
					preg_match_all("/(\w)\,(\w)/",$value['seat'], $s);
					// 最短停留
					preg_match("/(\d)([D|M])/", $array[$key]['minStay'], $md);
					if(isset($md[1]) && isset($md[2])){
						$mdAdd = (int)$md[1] * ( $md[2] == 'M' ? 30 * 24 * 60 * 60 : 24 * 60 * 60  );
					}

					$tpl['outFileCode']          = "";
					$tpl['DepartCity']           = $value['start'];
					$tpl['ArriveCity']           = $value['end'];
					$tpl['Routing']              = $value['routing'];
					$tpl['RoutingClass']         = isset($s[1][0]) && $s[1][0] == $s[2][0] ? $s[1][0]: $value['seat'];
					$tpl['FareBasis']            = $value['fare'];
					$tpl['OutboundDayTime']      = $value['allowWeek_1'];
					$tpl['InboundDayTime']       = $value['allowWeek_2'];
					$tpl['FcOutboundTravelDate'] = $value['allowDateStart'] != '' && $value['allowDateEnd'] != '' ?  date('Y-m-d',strtotime($value['allowDateStart'])).'>'.date('Y-m-d',strtotime($value['allowDateEnd'])): ''; 
					$tpl['FcInboundTravelDate']  = $tpl['FcOutboundTravelDate'] != '' && isset($mdAdd) ?  date('Y-m-d', strtotime($value['allowDateStart']) + $mdAdd ).'>'.date('Y-m-d',strtotime($value['allowDateEnd'])): ''; 
					// 销售日期 SalesDate
					$tpl['MinStay']              = $array[$key]['minStay'];
					$tpl['MaxStay']              = $array[$key]['maxStay'];
					$tpl['SalesPrice']           = $array[$key]['backLineFee'] != ''? $array[$key]['backLineFee'] : $array[$key]['singleLineFee'];  
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

		// 判断去程与回程是否相同
		$stay      = '';
		$start_2   = '';  // 回程出发
		$stay_2    = '';  // 回程中转
		$end_2     = '';  // 回程到达
		$array     = array();
		$stayMatch = array(
			'EK' => array('aircompany' => 'EK', 'stay' => 'DXB'),
			'EY' => array('aircompany' => 'EY', 'stay' => 'DXB'),
		);

		// 回程的查询结果回程出发与到达应相反
		if($array1['start'] != $array2['start']){
			$start_2 = $array2['end'];
		}
		// 回程的查询结果回程出发与到达应相反
		if($array1['end'] != $array2['end']){
			$end_2 = $array2['start'];
		}

		// 查询航路
		$routingResultId1    = $array1['start'].$array1['end'].'/'.$array1['aircompany'];
		$routingResultId2    = $array2['start'].$array2['end'].'/'.$array2['aircompany'];

		$_POST['aircompany'] = $array1['aircompany'];
		$_POST['start']      = $array1['start'];
		$_POST['end']        = $array1['end'];

		// 减少请求次数
		if(isset($this->cache['routingResult']) && !empty($this->cache['routingResult'][$routingResultId1])){
			$routingResult1    = $this->cache['routingResult'][$routingResultId1];
		}
		else{
			$Result1 = $this->searchFslByInput(true);
			if(empty($Result1['msg']) ){
				$routingResult1  = $Result1['array'];
				$this->cache['routingResult'] = array();
				$this->cache['routingResult'][$routingResultId1] = $routingResult1;
			}else{
				echo $Result1['msg'];
			}
		}

		// 查询航程的航班号
		// $flightModel         = model('flight');
		// $flightResult1       = $flight -> where("Fli_Airport = '{$array1['aircompany']}'") -> select();


		if(isset($routingResult1))
			// 相同航程
			if($routingResultId2 == $routingResultId1){

				// 相同查询结果
				$routingResult2    = $routingResult1;

			// 不同航程
			}else{

				$_POST['aircompany'] = $array2['aircompany'];
				$_POST['start']      = $array2['start'];
				$_POST['end']        = $array2['end'];

				// 减少请求次数
				if(isset($this->cache['routingResult']) && !empty($this->cache['routingResult'][$routingResultId2])){
					$routingResult2 = $this->cache['routingResult'][$routingResultId2];
				}
				else{
					$routingResult2 = $this->searchFslByInput(true);
					if(empty($Result2['msg']) ){
						$routingResult2 = $Result1['array'];
						$this->cache['routingResult'] = array();
						$this->cache['routingResult'][$routingResultId2] = $routingResult2;
					}else{
						echo $Result2['msg'];
					}
				}

			}


		// 利用 $routingResult1 扩充 $stayMatch
		if(!empty($routingResult1)){
			$stayMatch[$array1['aircompany']] = array(
				'aircompany' => $array1['aircompany'],
				'stay'       => ''
			);
			foreach ($routingResult1[$routingResultId1]['result'] as $stay) {
				$stayMatch[$array1['aircompany']]['stay'] .= $stay.',';
			}
			$stayMatch[$array1['aircompany']]['stay'] = rtrim($stayMatch[$array1['aircompany']]['stay'],',');
		}

		// 利用 $routingResult2 扩充 $stayMatch
		if(!empty($routingResult2)){
			$stayMatch[$array2['aircompany']] = array(
				'aircompany' => $array2['aircompany'],
				'stay'       => ''
			);
			foreach ($routingResult2[$routingResultId2]['result'] as $stay) {
				$stayMatch[$array2['aircompany']]['stay'] .= $stay.',';
			}
			$stayMatch[$array2['aircompany']]['stay'] = rtrim($stayMatch[$array2['aircompany']]['stay'],',');
		}

		if($array1['aircompany'] == $array2['aircompany']){
			// 相同航空公司，回程中转地相同
			if(isset($stayMatch[$array1['aircompany']]) && $array1['end'] != $stayMatch[$array1['aircompany']]['stay']){ // 有该航司中转城市，且目的地不是中转城市
				$stay = $stayMatch[$array1['aircompany']]['stay'];
			}

			// 回程中转地不同
			if(!empty($end_2)){
				if(isset($stayMatch[$array2['aircompany']]) && $array2['end'] != $stayMatch[$array2['aircompany']]['stay']){ // 有该航司中转城市，且目的地不是中转城市
					$stay_2 = $stayMatch[$array2['aircompany']]['stay'];
				}
			}

		}else{
			\BYS\Report::error('不能进行不同航空公司混舱规则！');
		}

		$array = array(
				'fare'          => "{$array1['fare']}/{$array2['fare']}",
				'ADVPDay'       => strtotime(preg_replace("/D/","day",$array1['ADVPDay'])) > strtotime(preg_replace("/D/","day",$array2['ADVPDay'])) ? preg_replace("/D/","",$array1['ADVPDay']): preg_replace("/D/","",$array2['ADVPDay']),
				'singleLineFee' => $array1['singleLineFee'] == ""? "":($array1['singleLineFee']+$array2['singleLineFee'])/2,
				'backLineFee'   => $array1['backLineFee'] == "" ? "":($array1['backLineFee']+$array2['backLineFee'])/2,
				'seat'          => ( isset($stayMatch[$array1['aircompany']]['stay']) ?  $array1['seat'].'-'.$array1['seat'] : $array1['seat']).'-'.( isset($stayMatch[$array2['aircompany']]['stay']) ? $array2['seat'].'-'.$array2['seat'] : $array2['seat'] ),
				'minStay'       => strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array1['minStay'])) > strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array2['minStay'])) ? $array2['minStay'] : $array1['minStay'],
				'maxStay'       => strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array1['maxStay'])) < strtotime(preg_replace(array("/D/","/M/"), array("day","month"), $array2['maxStay'])) ? $array1['maxStay'] : $array2['maxStay'],
				'allowDateStart'=> strtotime($array1['allowDateStart']) > strtotime($array2['allowDateStart'])? $array1['allowDateStart']:$array2['allowDateStart'],
				'allowDateEnd'  => strtotime($array1['allowDateEnd']) < strtotime($array2['allowDateEnd'])? $array1['allowDateEnd']:$array2['allowDateEnd'],
				'allowWeek_1'   => $array1['allowWeek'],
				'allowWeek_2'   => $array2['allowWeek'],
				'reTicket'      => "{$array1['reTicket']}/{$array2['reTicket']}",
				'start'         => $array1['start'],
				'end'           => $array1['end'],
				'stay'          => $stay,
				'aircompany'    => $array1['aircompany'],
				'routing'       => "{$array1['start']}-{$array1['aircompany']}-".($stay != ''? "{$stay}-{$array1['aircompany']}-{$array1['end']}-":"{$array1['end']}-").($start_2 != ''? ",{$start_2}-": '').($stay_2 != ''? "{$array2['aircompany']}-{$stay_2}-":"{$array2['aircompany']}-").($end_2 != ''? "{$end_2}": "{$array1['start']}") 
			);

		// \BYS\Report::p($array);

		return $array;
	}

	// ------------------------ FSD ------------------------

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
		$identity   = $_POST['index'];

		// 扩展命令
	 	if(isset($_POST['index'])){
	 		$identity = '<'.$identity;
	 	}

		$date = array( 
			'fare'=> $fare,
			'start'=> $start,
			'end'=> $end,
			'startDate'=> $startDate,
			'aircompany'=> $aircompany,
			'from'=> $from
		);

		$command = strtoupper('XS/FSD'.$start.$end.'/'.$startDate .'/'.$aircompany.'/'.'#*'.$fare.$identity.($from != '公布运价' ? '///#'.$from : ''));
		$array  = $_POST['index'] === '' ? $fsd->fare(array(0,1,2), $date, $command) : $fsd->fare(array(0=>$_POST['index'],1,2), $date, $command); 	
		$log = $fsd->rtTmp();

		echo json_encode(array('command'=> $command,'aircompany'=> $aircompany, 'fare'=> $fare, 'array'=>$this->assignItem($array), 'data'=>$array, 'log'=>$log) ); 
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

	// ------------------------ 其他 ------------------------

  public function returnEnd(){
  	return array(
				// 'SAV,ATL,ZUH,ZTH,AGS,ZRH,ZQN,ZNZ,ZLO,ZIH,ZHA,ZCO,ZCL,ZBF,ZAL',
				// 'ZAG,ZAD,YZV,YZR,YZF,YYY,YYT,YYR,YYJ,YYF,YYD,YYC,YYB,YXY,YXX',
				// 'YXU,YXT,YXS,YXJ,YXH,YXE,YXC,YWL,YWK,YWG,YVR,YVO,YUY,SLC,YMQ',
				// 'YTS,YTM,YSJ,YSB,YQZ,YQX,YQU,YQT,YQR,YQQ,YQM,YQL,YQG,YQB,YOW',
				// 'YNZ,YNT,YNJ,YMM,YLW,YKF,YKA,YIW,YIN,YIH,YHZ,YTO,YGP,YGK,YGJ',
				// 'YFC,YEA,YDF,YCU,YCG,YCD,YBP,YBL,YBG,YBC,YAM,XUZ,XNN,SGU,XMN',
				// 'SIA,XIL,XIC,XFN,WXN,WUX,WUS,WUH,WRO,SBN,WNZ,WLG,WKJ,WEH,WDH',
				// 'WAW,SRZ,VVC,VUP,VTE,VSA,VRN,VRA,VQS,IND,VOL,VNO,VLN,VLC,VIX',
				// 'VIJ,VIE,VGO,VER,VCE,VAR,VAA,UYN,URC,UME,ULN,UIO,UIB,UDI,UBJ',
				// 'FWA,EVV,TYN,TXN,BER,SPI,PIA,CHI,TUN,MLI,TTJ,TSR,TSN,TSJ,TSE',
				// 'TRU,TRS,TRN,CMI,TRD,TRC,TPQ,TPP,TPE,BMI,TOY,TOS,YUM,TNH,TNA',
				// 'TMP,TLV,TLS,TLL,TUS,TKS,TIV,TIA,THE,TGU,TGO,TGD,TCQ,TCO,TCB',
				// 'TBS,TAP,TAO,TAM,TAK,TAG,TAE,TAC,SZX,SZG,SYX,PHX,SYO,SYD,SXB',
				// 'PGA,SWA,SVQ,SVG,GCN,SUF,SUB,STR,STM,MOB,STI,SSH,SSA,MGM,SRG',
				// 'SPU,HSV,BHM,SOF,SNN,AIY,SMR,SMI,HOB,SLZ,SLP,ABQ,SLA,SKP,SKG',
				// 'SKB,SJW,SJU,MHT,SJP,SJO,SJJ,SJD,OGG,SIN,LNY,LIH,SHP,SHE,SHB',
				// 'KOA,ITO,SFT,HNL,CRW,SDJ,CKB,SCY,SCN,SCL,MSN,SBZ,MKE,LSE,GRB',
				// 'EAU,AUW,ATW,SAP,TYS,SAL,RZE,RUH,RTM,RTB,TRI,MEM,ROV,ROT,ROS',
				// 'CHA,MLW,BNA,SYR,RMF,RLG,RJK,RIX,RIS,SWF,RHO,RGN,RGL,REX,REP',
				// 'REC,ROC,NYC,RCH,RBR,RAR,ITH,RAO,RAK,QRO,PXO,PXM,ISP,PVR,PVK',
				// 'PVH,HPN,PUY,PUS,ELM,PTY,BUF,PSO,BGM,PRN,PRG,ART,PPS,PPN,POZ',
				// 'POA,ALB,PNQ,PNH,PNA,PMW,PMO,PMI,PMC,PLZ,RNO,PIU,LAS,OMA,LNK',
				// 'EAR,MYR,GSP,PHC,FLO,PFB,PER,PEN,PEM,PEI,PEG,CHS,PCL,CAE,PBC',
				// 'PAZ,PAP,RAP,PAD,OVD,OUL,OUA,BUH,PIR,OSL,ORK,FSD,RST,OPO,DLH',
				// 'ONJ,PWM,OKJ,BGR,OKA,OIT,OIM,TVC,ODS,OCC,OBO,OAX,MQT,NVT,NVA',
				// 'NUE,NTQ,NTG,YAO,NNG,NKG,NGS,NGO,NGB,NEV,NDG,NCL,NCE,NBO,NAT',
				// 'NAS,NAP,NAN,MZT,MZL,MKG,MYJ,MUC,MTY,MTT,MTR,MBS,LAN,MSQ,GRR',
				// 'FNT,DTT,MRS,CMX,MPM,MPH,AZO,LUL,MNL,MMY,MMB,JAN,MLM,GPT,MLE',
				// 'STL,MLA,SGF,MKC,MIG,MID,JLN,COU,MSO,HLN,MGA,GTF,MFM,FCA,MEX',
				// 'BZN,MEL,BIL,MEC,MDZ,BOS,MDG,MDE,MCZ,MCT,MCP,ACK,SBY,BWI,PVD',
				// 'SHV,MBJ,MBE,MBA,MAZ,MAR,MAO,MAN,MAM,MAJ,MSY,MAD,MAB,MAA,LZO',
				// 'LZH,LZC,LYS,LXA,LWO,LVI,LUX,LUN,LUM,LUG,MLU,LSC,LRM,LFT,LPB',
				// 'LOS,LNZ,LCH,BTR,LMM,LLW,LLA,LJU,LJG,AEX,LIS,LIR,MIL,LIM,SDF',
				// 'LHW,LGG,PAH,LFW,LEX,CVG,LET,LEJ,LED,LDE,LDB,LON,MTJ,LCG,LCE',
				// 'LCA,SBS,GUC,LBA,EGE,DRO,DEN,LAO,COS,LAD,KWL,KWI,KWE,KWA,KVA',
				// 'KUL,KUH,KUF,KTW,KSD,KSC,KRY,KRT,KRS,KRR,KRL,KRK,KOW,KOJ,ASE',
				// 'MES,KMQ,KMJ,KMI,KMG,KLX,KLU,KLO,KKJ,KIV,KIN,KIJ,KHN,KHI,KHH',
				// 'KHG,KGS,REK,KCZ,KBV,IEV,JZH,JUZ,JUL,JTR,JSI,JOI,JOG,JNZ,JNG',
				// 'JNB,JMU,HVN,JMK,HFD,JKH,JKG,JJN,JIU,JIB,JIA,JHG,JGS,MHK,JER',
				// 'JED,JDZ,ICT,SNA,JAL,SMX,IWK,IWJ,SAC,OSA,SJC,IST,SFO,ISG,IQT',
				// 'IQQ,IOS,INN,CSL,INC,IMP,IKI,THR,IGU,SBA,SAN,RDD,IBZ,IBE,IAS',
				// 'PSP,ONT,HYN,HYD,OAK,HUX,HTN,HTA,MRY,HSG,LAX,HRK,HRG,HRE,HRB',
				// 'FAT,CLD,BUR,HOG,BFL,ACV,TYO,SHR,HLH,HLD,HKT,HKG,HKD,HIJ,HGH',
				// 'HFE,HET,HEL,JAC,HAV,HAU,HAN,HAM,HAJ,HAC,GYS,GYN,GYE,GWT,GVA',
				// 'GUM,CPR,GUA,COD,SEA,PSC,GRZ,GEG,ROA,RIC,ORF,GPA,GOT,GOA,GLA',
				// 'RIO,GIB,GHB,GGT,WAS,GEO,CHO,GDN,GDL,BTV,FUK,FUJ,FUG,FTE,FSZ',
				// 'VPS,TPA,FSC,FRS,FRA,FPO,FOC,TLH,FNJ,FNC,FNA,FLR,SRQ,FLN,FMY',
				// 'PNS,FLA,FKS,FIH,FDH,FDF,ROM,PBI,MLB,MIA,ORL,FAO,JAX,GNV,FLL',
				// 'EYW,DAB,EVN,RDM,ANK,PDX,NQT,ELS,OTH,MFR,ELH,EJA,EUG,EDI,EBL',
				// 'EBB,TUL,EAS,OKC,DYG,DXB,DVO,LAW,DUS,DUR,TOL,DUD,DUB,DAY,DTM',
				// 'DSN,CMH,DRS,CLE,DPS,DOK,DOH,DNK,DNH,DMM,MOW,DLM,CAK,DLC,DLA',
				// 'DKR,DGT,DGO,TYR,SJT,DEL,DDG,SAT,DBV,MFE,MAF,DAX,DAT,LRD,LBB',
				// 'CZX,CZM,CYO,CWB,HRL,CVM,HOU,CUZ,CUU,CUN,CUE,CUC,CTU,SPK,CTG',
				// 'CTA,CSX,ILE,GGG,CRD,CPX,CPT,ELP,CPO,CPH,CPE,DFW,COR,COO,CRP',
				// 'CNX,CNS,BHZ,CLL,CAS,BRO,BPT,CME,CMB,CLY,AUS,CLQ,CLO,AMA,CLJ',
				// 'ACT,ABI,CKY,CKG,SCE,CJU,CJC,CIX,CIH,CIF,PIT,PHL,CHQ,HAR,CHG',
				// 'ERI,CGR,CGQ,CGO,CGN,SAO,CGB,AVP,CEN,CEB,ABE,PAR,CCU,CCS,CCP',
				// 'CAP,RDU,CAI,CAG,GSO,FAY,BZG,BZE,EWN,BVB,CLT,AVL,BUD,MOT,FAR',
				// 'BSL,BSB,BRU,BRS,BIS,BRN,BRI,BRE,BQN,IDA,BPS,BOI,BON,BOM,SUX',
				// 'BOG,BOD,BNE,DSM,DBQ,BLZ,BLR,BLQ,BLL,BKK,BJX,BXN,BJM,BJL,CID',
				// 'BIQ,ALO,BIA,BHY,BHX,FAI,ANC,BGO,FYV,BGI,BGA,BFS,TXK,BEY,BEL',
				// 'BEG,BDS,LIT,BDA,BCN,BCD,BBA,BAV,BAQ,BAH,FSM,AYT,AXT,AXM,AXA',
				// 'ILG,ZVE,ZTF,AUC,AUA,WRL,ATQ,SOW,ATH,ASU,PUB,ASB,PRC,STO,ARI',
				// 'AQP,APW,AOK,AOJ,AOI,ANU,ANF,PKB,AMS,AMM,AMD,MEI,MCK,ALL,ALG',
				// 'ALF,ALC,MCE,ALA,AKU,AKL,AKJ,AJU,LBL,AGU,AGT,JMS,AGP,AGA,IGM',
				// 'AES,BUE,ADZ,ADL,ADD,IZM,HYS,HON,DVL,DUJ,ACC,ACA,ABZ,ABV,CEZ',
				// 'CDR,AIA,AAR,AAL'
				);
  }

  public function searchXfsdByDefault(){
  	$defalut = array(
			'end'   => $this->returnEnd()
  	); 

		foreach ($defalut['end'] as $end) {
			$_POST = array(
	  		'startDate'  => '15SEP',
	  		'aircompany' => 'UA',
	  		'start' => 'BJS',
	  		'private' => '',
	  		'tripType' => '',
	  		'other' => '',
	  		'end' => $end
			);

			$this->searchXfsdByInput(true);
			echo $end."<br>";
			ob_flush();
			flush();
			sleep(30);
		}
  }

  public function insertAllXfsdResult(){
    import('vender/eterm/app.php');

  	$xfsd       = new \Xfsd($_SESSION['name'], $_SESSION['password'], $_SESSION['resource']);
  	$start      = 'BJS';
  	$startDate  = '15SEP';
  	$aircompany = 'UA';
  	$tripType   = '';
  	$code       = '';
  	$other      = '';
  	$ab_flag    = '';
  	 
  	$endListArr = $this->returnEnd();

  	foreach ( $endListArr as $endList) {
  		$endArr   = explode(',', rtrim($endList,','));
  		$array    = array(); 
  		foreach ($endArr as $end) {
				$command = $this->toXfsdCommand($start, $end, $startDate, $aircompany, $tripType, $code, $other );
	  		$result  = $this->hasCmdSource(array('command'=>$command, 'office'=>$_SESSION['resource']), 'xfsd');
	  		echo $end."<br>";

	  		if( is_array($result) && isset($result['Id']) ){ 

					$xfsd->wtTmp($result['Detail']);
					$resultArr                 = $ab_flag ? $xfsd->analysis(array(2,3,4)) : $xfsd->analysis(array(2,3));
					$array[$end]               = $resultArr;
					$id                        = $result['Id'];
					$array[$end]['id']         = $result['Id'];
		  		$array[$end]['from']       = $code==''?'':$code;
					$array[$end]['aircompany'] = $aircompany;
					$array[$end]['startDate']  = $startDate;
					$array[$end]['length']     = count($resultArr);
					$array[$end]['command']    = $command;
					$array[$end]['other']      = $other;
					// var_dump($array[$end])
					// $this->updateCmdResult($array[$end], $id, $command, 'xfsd');
					// 声明
	  		}else{
		  		var_dump($result);
		  		echo "<br>";
	  		}

  		}
  		$this->saveXfsdResult($array);
  		echo $endList." <font color='red'> time:".date('H:i:s',time())."</font><br>";
  		ob_flush();
			flush();
			sleep(3);
  	}
  }
}