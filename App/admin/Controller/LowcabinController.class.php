<?php
namespace admin\Controller;
use BYS\Controller;
class LowcabinController extends Controller {
	public function index(){
		$this->display();
	}

	// 占位
	public function saveSeat(){
		$this->display();
	}

	// 占位计划任务初始化
	public function seatPlanDefaultSet(){

		// strtoupper(date('dM',time() + (1 * 24 * 60 * 60)))
		$outboundStartDate = time() + (1  * 24 * 60 * 60);
		$outboundEndDate   = time() + (46 * 24 * 60 * 60);
		$inboundStartDate  = time() + (1  * 24 * 60 * 60);
		$inboundEndDate    = time() + (61 * 24 * 60 * 60);
		$m = model('plan_avh_source');
		// 清空  plan_avh 由于 avh 具有过期，重复检验功能 avh_source avh_result 不清空

		$plans = array(
			array(
				'start'=> "PEK",
				'end'=> "DXB",
				'startDate'=> $outboundStartDate,
				'endDate'=> $outboundEndDate,
				'aircompany' => 'EK'
			),
			array(
				'start'=> "DXB",
				'end'=> "PEK",
				'startDate'=> $inboundStartDate,
				'endDate'=> $inboundEndDate,
				'aircompany' => 'EK'
			),
			array(
				'start'=> "PVG",
				'end'=> "DXB",
				'startDate'=> $outboundStartDate,
				'endDate'=> $outboundEndDate,
				'aircompany' => 'EK'
			),
			array(
				'start'=> "DXB",
				'end'=> "PVG",
				'startDate'=> $inboundStartDate,
				'endDate'=> $inboundEndDate,
				'aircompany' => 'EK'
			),
			array(
				'start'=> "CAN",
				'end'=> "DXB",
				'startDate'=> $outboundStartDate,
				'endDate'=> $outboundEndDate,
				'aircompany' => 'EK'
			),
			array(
				'start'=> "DXB",
				'end'=> "CAN",
				'startDate'=> $inboundStartDate,
				'endDate'=> $inboundEndDate,
				'aircompany' => 'EK'
			),
		);

		if(isset($_POST['returnPlan'])){
			echo json_encode(array('result'=>$plans));
			return;
		}
		$m->deleteAll();

		$addAll = array();
		$updateAll = array();
		$updateWhereAll = array();
		foreach($plans as $plan){
			$result = $m->where("`Start` = '{$plan['start']}' AND `End` = '{$plan['end']}'")->select();
			if($result){
				// 此处用清空表代替，暂留逻辑
				// 日期不对：startDate < 当前天数+1
				// if(strtotime($result[0]['StartDate']) < $outboundStartDate){
				// 	array_push($updateWhereAll , "`Id` = {$result[0]['Id']}");
				// 	array_push($updateAll,  array(
				// 		'StartDate' => $plan['startDate'],
				// 		'EndDate'   => $plan['endDate']
				// 	));
				// }
			}else{
				// 没有该数据
				$dateRange = ($plan['endDate'] - $plan['startDate']) / (24 * 60 * 60);
				for($i = 0; $i < $dateRange; $i++){
					// 隔5天
					if($i%5 === 0)
						$addAll[] = array(
							'Start'      => $plan['start'],
							'End'        => $plan['end'],
							'Aircompany' => $plan['aircompany'],
							'StartDate'  => strtoupper(date('dM', $plan['startDate'] + $i * 24 * 60 * 60)),
							'EndDate'    => strtoupper(date('dM', $plan['startDate'] + ($i+4) * 24 * 60 * 60)),
						);
				}
			}
		}
		if(!empty($addAll)){
			$m->addAll($addAll);
			var_dump('addAll', $addAll);
		}
		if(!empty($updateAll)){
			$m->updateAll($updateWhereAll, $updateAll);
			var_dump('updateAll', $updateAll);
		}

	}

	// 占位计划运行
	public function seatPlanRun(){
		$eterm        = reflect('eterm');
		$m_plan       = model('plan_avh_source');

		// 调用 defalutSet，清空plan 并回填相应数据
		// $this->seatPlanDefaultSet();

		// 读取 status = 0 的信息
		$result_plan = $m_plan->where('`Status` = 0')->limit('10')->select();
		// 更新 plan_source sid( 全部的avh id) status , 利用守护进程 每天晚上跑，检测回填source id 是不是5条，如果是则status = 1
		if($result_plan){
			$updateAll      = array();
			$updateWhereAll = array();
			foreach ($result_plan as $plan) {
				$_POST['start']      = $plan['Start'];
				$_POST['end']        = $plan['End'];
				$_POST['startDate']  = $plan['StartDate'];
				$_POST['endDate']    = $plan['EndDate'];  
				$_POST['aircompany'] = $plan['Aircompany'];    
				$_POST['other']      = 'D';   
				$avh_run_result      = $eterm->searchAvhByInput(true);
				if(!empty($avh_run_result['id'])){
					// plan中的 sid 由多个 id 组合 
					// 日期间隔为5天
					if(count($avh_run_result['id']) >= 5){
						var_dump($plan['Id'], $avh_run_result['id'],"<br>");
						array_push($updateWhereAll, "`Id` = {$plan['Id']}" );
						array_push($updateAll, array(
							'Status' => 1,
							'Sid'    => implode($avh_run_result['id'], ',')
						));
					}
				}
				ob_flush();
				flush();
			}

			// 更新全部
			$m_plan->updateAll($updateWhereAll, $updateAll);
		}
	}

	// 保存记录
	public function saveHoldSeat(){
		$note    = json_decode($_POST['note'], true);
		$source  = $_POST['source'];

		// 判断source中是否有，KeyWord, 去除空格的20个字符
		$keyWord = substr(preg_replace("/\s|\n|\r|\t/", '', $source), 0, 50);
		$result  = $this->hasHoldSeatSource($keyWord);
		if(!$result){
			$sid = $this->saveHoldSeatSource($source, $note, $keyWord);
		}
		if(isset($sid)){
			if($this->saveHoldSeatResult($note, $sid)){
				echo json_encode(array('status'=>1));
				return; 
			}
		}
		echo json_encode(array('status'=>0, 'msg'=> '添加失败，已存在'));
	}

	// 查询是否有占位记录
	public function hasHoldSeatSource($keyWord){
  	$m = model('hold_seat_source');
  	$result = $m ->where('`KeyWord` ="'.$keyWord.'" ')->select();
  	// 为空返回false
  	if(!$result || empty($result[0]) ) return false;
  	$col = $result[0]; // 仅一条
		if ( isset($col['KeyWord']) && $keyWord == $col['KeyWord'] ) 
			return $col;

  	return false;
	}

	// 保存占位记录
	public function saveHoldSeatSource($source = '',$note = array(), $keyWord = ''){
  	$m = model('hold_seat_source');
  	$add = array(
  		'KeyWord' => $keyWord,
  		'Status'  => 0, // 用于检测是否需要降舱处理
  		'Source'  => $source,
  		'Note'    => json_encode($note),
  		'PNR'     => $note['pnr']
  	);
  	return $m->add($add);
  }

  // 保存占位结果记录
  public function saveHoldSeatResult($array = array(), $id = NULL){

  	if( !isset($array['pnr'])) return;
		if( $id == NULL) return;
		$m_source   = model('hold_seat_source');
		// 回填 解析详情
  	$m          = model('hold_seat_result');
  	// 默认第一个为航空公司
  	$aircompany = substr($array['note'][0]['flight'], 0, 2);
  	$note       = $array['note'];
  	$addAll     = array();  // 添加数据库的字段

		foreach($note as $key => $value) {
			$addAll[] = array(
				'Client'        => $array['client'],
				'Flight'        => $value['flight'],
				'Cabin'         => $value['cabin'],
				'Depart'        => $value['depart'],
				'Arrive'        => $value['arrive'],
				'Date'		      => $value['date'],
				'Depart_Time'   => $value['departTime'],
				'Arrive_Time'   => $value['arriveTime'],
				'Aircompany'    => $aircompany,
				'Sid'           => $id,
			);
		}

		// 保存组数据
		return $m->addAll($addAll);
  }

  // 查询占位中是否有该航段记录
  public function searchHoldSeat(){
  	$note      = json_decode($_POST['note'], true);
  	$parseNote = $note['note'];

  	$m_result  = model('hold_seat_result');
  	$m_source  = model('hold_seat_source');
  	$where_id = array();
  	// 获得筛选条件
  	foreach($parseNote as $list_note){
  		$searchCabinList = rtrim($list_note['searchCabinList']);
  		if($searchCabinList && $searchCabinArray = explode(',', $searchCabinList)){
  			// 生成舱位筛选条件
  			$where_searchCabin = '';
  			foreach ($searchCabinArray as $searchCabin) {
  				$where_searchCabin .= "'$searchCabin',";
  			}
  			$where_searchCabin = rtrim($where_searchCabin,',');
		  	$result_result = $m_result->where("`Arrive`='{$list_note['arrive']}' AND `Depart`='{$list_note['depart']}' AND `Flight`='{$list_note['flight']}' AND `Cabin` in ({$where_searchCabin})")->select();
	  		$m_result->reset();
	  		if($result_result && !in_array($result_result[0]['Sid'], $where_id))
	  			array_push($where_id, $result_result[0]['Sid']);
	  	}
  	}

  	// 为了获得 sid 不重复，筛选：出发、到达、航班号
  	if(!empty($where_id)){
  		// 组合sid 
  		$result_source = $m_source->where("Id in (".implode(',', $where_id).")")->select();
  		if($result_source){
	  		echo json_encode(array('status'=>1, 'result'=>$result_source));
	  		return;
	  	}
  	}
  	echo json_encode(array('status'=>0, 'msg'=> '无数据'));
  }

  // 删除占位记录
  public function deleteHoldSeat(){
		if(isset($_POST['sid'])){
			$sid           = $_POST['sid'];
			$m_source      = model('hold_seat_source');
			$m_result      = model('hold_seat_result');
			$result_source = $m_source->where("`Id` = {$sid}")->delete();
			$result_result = $m_result->where("`Sid` = {$sid}")->delete();
			echo json_encode(array('msg'=>'删除成功', 'status'=>$result_source.$result_result));
		}

  }

	// 查询所有的降舱记录
	public function searchAll(){
		$m = model('low_cabin_source');
		$result = $m->select();
		echo json_encode(array('result'=>$result));
	}

	// 查询所有的result情况
	public function searchResult(){
		$sid           = $_POST['sid'];
		$m_result      = model('low_cabin_result');
		$result_result = $m_result->where("`Sid` = {$sid}")->delete();
		echo json_encode(array('result'=>$result_result));
	}

	// 查询所有的cabin

	public function deleteNote(){
		if(isset($_POST['sid'])){
			$sid           = $_POST['sid'];
			$m_source      = model('low_cabin_source');
			$m_result      = model('low_cabin_result');
			$m_group       = model('low_cabin_group');
			$result_source = $m_source->where("`Id` = {$sid}")->delete();
			$result_result = $m_result->where("`Sid` = {$sid}")->delete();
			$result_group  = $m_group->where("`Sid` = {$sid}")->delete();
			echo json_encode(array('msg'=>'删除成功', 'status'=>$result_source.$result_result.$result_group));
		}

	}

	// 保存记录
	// 通过3个方法，hasSource/saveSource/saveResult/saveGroup
	public function saveNote(){
		$note     = $_POST['note'];
		$source   = $_POST['source'];
		if(empty($note)) return;

		// 判断source中是否有，KeyWord, 去除空格的20个字符
		$keyWord = substr(preg_replace("/\s|\n|\r|\t/", '', $source), 0, 50);
		$result  = $this->hasSource($keyWord);
		if(!$result){
			$sid = $this->saveSource($source, json_encode($note), $keyWord);
		}
		if(isset($sid)){
			if($this->saveResult($note, $sid)){
				echo json_encode(array('status'=>1));
				return; 
			}
		}
		echo json_encode(array('status'=>0, 'msg'=> '添加失败，已存在'));
	}

  // source 是否存在
  private function hasSource($keyWord = ''){
  	$m = model('low_cabin_source');
  	$result = $m ->where('`KeyWord` ="'.$keyWord.'" ')->select();
  	// 为空返回false

  	if(!$result || empty($result[0]) ) return false;
  	$col = $result[0]; // 仅一条
		if ( isset($col['KeyWord']) && $keyWord == $col['KeyWord'] ) 
			return $col;

  	return false;
  }

  // 保存 source
  private function saveSource($source = '',$note = '', $keyWord = ''){
  	$m = model('low_cabin_source');
  	$add = array(
  		'KeyWord' => $keyWord,
  		'Status'  => 0, // 用于检测是否需要降舱处理
  		'Source'  => $source,
  		'Note'    => $note,
  	);
  	return $m->add($add);
  }

  // 保存 result
  private function saveResult($array = array(), $id = NULL){

  	if( !isset($array['client'])) return;
		if( $id == NULL) return;
		$m_source   = model('low_cabin_source');
		// 回填 解析详情
  	$m          = model('low_cabin_result');
  	// 回填 可降舱位
  	$m_cabin    = model('cabin_rule');
  	// 初始化 基础数据
  	$aircompany = substr($array['note'][0]['flight'], 0, 2);
  	$cabin      = $array['note'][0]['cabin'];
  	$note       = $array['note'];
  	$routingLen = count($array['note']);
  	$addAll     = array();  // 添加数据库的字段
 		$cabin_list = array();  // 待追位的舱位
 		$group_add  = array();  // 航段信息，用于储存

 		// 根据QTE获得价格等fare等信息
 		import('vender/eterm/app.php');
 		$qt        = new \Qt('av66', 'av66av66', 'BJS248');
 		$qteResult = $qt->qte($array['pnr'], $array['note']);
 		$note      = $qteResult['note'];  // 更新ss中转类型
 		if(!empty($qteResult['log'])) $m_source->where("`Id` = {$id}")->update(array('Log'=>$qteResult['log']));
 		// var_dump($qteResult);
 		// return;
		foreach($note as $key => $value) {
			$cur_aircompany = substr($value['flight'], 0, 2);
			$cur_cabin      = $value['cabin'];

			// 获得航空公司
			if( $aircompany != $cur_aircompany){
				$aircompany = $cur_aircompany;
			}

			// 当匹配的舱位不一致时，重新获得可降舱位列表
			if( $cabin != $cur_cabin || empty($cabin_list) ){
		 		$m_cabin -> prepare("SELECT A.Cab_Code AS Cabin, A.Cab_Description As Description, A.Cab_BasicCode AS CabinLevel, A.Cab_CabinLevel AS LevelList FROM basis_cabin_rule AS A, ( SELECT * FROM basis_cabin_rule WHERE `Cab_AircompanyCode` = '{$aircompany}' AND `Cab_Code` = '{$cur_cabin}' ) AS B WHERE B.Cab_BasicCode = A.Cab_BasicCode AND B.Cab_CabinLevel > A.Cab_CabinLevel AND A.`Cab_AircompanyCode` = '{$aircompany}'");
		 		$cabin_list_result = $m_cabin -> execute();
		 		if(is_array($cabin_list_result)){
		 			$cabin_list = array(); // 清空上次数据
			 		foreach ($cabin_list_result as $row) {
			 			array_push($cabin_list, $row['Cabin']);
			 		}		 		
		 		}
			}

			// 生成group数据
			if(!isset($group_add[$value['group']]) || empty($group_add[$value['group']])){
				$group_add[$value['group']] = array();
				if(is_array($cabin_list_result)){
			 		foreach ($cabin_list_result as $cabinDate) {
						$fsi = "XS FSI/{$aircompany}\n";
						// 区分 group 
						foreach ($note as $index => $line) {
							$fsi .= "S {$cur_aircompany}   ".substr($line['flight'], 2).($cur_cabin == $line['cabin'] ? $cabinDate['Cabin'] : $line['cabin'])."{$line['date']} {$line['depart']}{$line['departTime']}".( substr($line['arriveTime'],4,2) == '+1'? ">".substr($line['arriveTime'],0,4):" {$line['arriveTime']}")."{$line['arrive']}0{$line['routingType']}    76W\n";
						}
			 			$group_add[$value['group']][] = array(
				 			'Sid'          => $id,
				 			'GroupId'      => $value['group'],
				 			'Cabin'        => $cabinDate['Cabin'],
				 			'CabinLevel'   => $cabinDate['CabinLevel'],
				 			'CabinInLevel' => $cabinDate['LevelList'],
				 			'Fsi'          => $fsi,
			 			);
			 		}
			 	}
			}

			if(empty($cabin_list)) $status = -1; 
			$addAll[] = array(
				'Client'        => $array['client'],
				'PNR'           => $array['pnr'],
				// 'Price'         => isset($fareFee)? $fareFee : NULL,
				// 'Total_Price'   => isset($totalFee)? $totalFee : NULL,
				// 'FareBasis'     => isset($routing) ? json_encode($routing) : NULL,
				'Price'         => isset($qteResult['fareFee'])? $qteResult['fareFee'] : NULL,
				'Total_Price'   => isset($qteResult['totalFee'])? $qteResult['totalFee'] : NULL,
				'FareBasis'     => isset($qteResult['routing']) ? json_encode($qteResult['routing']) : NULL,
				'Flight'        => $value['flight'],
				'Cabin'         => $value['cabin'],
				'Depart'        => $value['depart'],
				'Arrive'        => $value['arrive'],
				'Date'		      => $value['date'],
				'Routing_Group' => $value['group'],
				'Routing_Type'  => isset($value['routingType'])?$value['routingType']: ' ',
				'Depart_Time'   => $value['departTime'],
				'Arrive_Time'   => $value['arriveTime'],
				'Note_Status'   => $value['status'],
				'Aircompany'    => $aircompany,
				// 临时降舱列表
				'LC_Cabin_List' => $value['cabinList'],
				// 'LC_Cabin_List' => implode($cabin_list, ','),
				'Sid'           => $id,
				'LC_Status'     => isset($status)? $status: NULL
			);
		}

		// 保存组数据
		if($m->addAll($addAll)){
			return $this->saveGroup($group_add);
		}
		return false;
  }

  // 保存 group
  private function saveGroup($array = array()){
  	if(empty($array)) return;
  	// 回填 组舱位
  	$m_group    = model('low_cabin_group');
  	$addAll     = array();
  	foreach ($array as $groupIndex => $group) {
  		foreach ($group as $data) {
	  		array_push($addAll, array(
	  			'Sid'          => $data['Sid'],
	  			'GroupId'      => $data['GroupId'],
	  			'Cabin'        => $data['Cabin'],
	  			'CabinLevel'   => $data['CabinLevel'],
	  			'CabinInLevel' => $data['CabinInLevel'],
	  			'Fsi'          => $data['Fsi'],
	  		));
  		}
  	}
  	return $m_group->addAll($addAll);
  }

  // 取数组的最小值
  public function array_min($array = array()){
  	if(empty($array)) return;
  	$min = $array[0];
  	$minKey = 0;
  	foreach ($array as $key => $value) {
  		if($value <= $min ){
  			$min = $value;
  			$minKey = $key; 
  		}
  	}
  	return array('value' => $min, 'key'=>$minKey);
  }

  public function tmpRunByPrice(){
		import('vender/eterm/app.php');
		$etermLib = reflect('eterm');
		$m_source = model('low_cabin_source');
		$m_result = model('low_cabin_result');

		// 未跑数据
  	$result_source = $m_source -> where('Status = 0')->select(); // ->limit('3')
  	if(!$result_source) return;

  	$qt           = new \Qt('av66', 'av66av66', 'BJS248');
  	$source_array = array(); // 最终返回结果
  	$alert        = false;   // 是否开启报警
  	foreach ($result_source as $source) {
  		$result_result = $m_result->where("`Sid` = {$source['Id']}")->select();
  		// 直接查询 QTB:/航空公司与当前价格对比
  		$source_parse  = json_decode($source['Note'],true);
  		$pnr           = $result_result[0]['PNR'];
  		$qtbResult     = $qt->qtb($pnr, $source_parse['note']);
  		// var_dump($qtbResult);

  		if($qtbResult['totalFee'] != 0 && ($qtbResult['fareFee'] < $result_result[0]['Price'] || $qtbResult['totalFee'] < $result_result[0]['Total_Price'])){
  			$alert = true;
  			$source_array[$source['Id']] = array('status'=> $alert, 'source'=>$source['Source'], 'result'=> $result_result,'msg'=>\BYS\Report::printLog());
  		}
  		$source_array[$source['Id']]['qtb'] = array(
					'fareFee' => $qtbResult['fareFee'],
					'totalFee'=> $qtbResult['totalFee'],
					'routing' => $qtbResult['routing'],
					'currency'=> $qtbResult['currency'],
					'pnr'     => $pnr
				);
  		ob_flush();
  		flush();
  		// sleep(3);
  	}

  	echo json_encode($source_array);
  }

  // 临时计划任务
  public function tmpRun(){
		import('vender/eterm/app.php');
		$eterm    = reflect('eterm');
		$m_source = model('low_cabin_source');
		$m_result = model('low_cabin_result');

		// 未跑数据
  	$result_source = $m_source -> where('Status = 0')->select(); // ->limit('3')
  	if(!$result_source) return;
  	$avh          = new \Avh('av66', 'av66av66', 'BJS248');
  	$av           = new \Av('av66', 'av66av66', 'BJS248');
  	$source_array = array();
  	foreach ($result_source as $source) {
  		$result_result = $m_result->where("`Sid` = {$source['Id']}")->select();

  		foreach($result_result as $rKey => $rVal){
  			if($rVal['LC_Cabin_List'] == '') continue; 

  			$av->command("AV:{$rVal['Flight']}/{$rVal['Date']}");
  			$av_result = $av->parseSrource("{$rVal['Depart']}{$rVal['Arrive']}");
  			if($av_result['depart'] == $rVal['Depart'] && $av_result['arrive'] == $rVal['Arrive']){
  				$target_cabin = explode(',', $rVal['LC_Cabin_List']);
  				if(empty($target_cabin)) continue;
  				$result_result[$rKey]['LC_Log'] = $av_result;
  				foreach ($target_cabin as $tv) {
  					// 当匹配到数据或A时
  					if(isset($av_result['cabin'][$tv]) && ($av_result['cabin'][$tv] >=1 || $av_result['cabin'][$tv] === 'A')){
  						$result_result[$rKey]['LC_Has_Low'] = 1;
  						$result_result[$rKey]['LC_Cabin'] .= $tv."({$av_result['cabin'][$tv]})".',';
  					}
  				}
  				rtrim($result_result[$rKey]['LC_Cabin'], ',');
  			}else{
  				continue;
  				// var_dump($av_result, $av->rtTmp());
  			}
  		}

  		$alert = false;
  		// 是否开启提示
  		foreach ($result_result as $rKey => $rVal) {
  			if($rVal['LC_Cabin_List'] != '' && $result_result[$rKey]['LC_Has_Low'] != 1){
  				break;
  			}
  			// 到最后时
  			if($rKey == count($result_result)-1){
	  			if($rVal['LC_Cabin_List'] != '' && $result_result[$rKey]['LC_Has_Low'] != 1){
	  				break;
	  			}else{
	  				$alert = true;
	  			}
  			}
  		}

  		$source_array[$source['Id']] = array('status'=> $alert, 'source'=>$source['Source'],'result'=> $result_result, 'msg'=>\BYS\Report::printLog());
  		ob_flush();
  		flush();

  		// 默认一个中转城市
  		// if(count($depart)>1){
				// // $_POST['other'] = ;   
  		// 	$_POST['end'] = $depart[1]['Arrive'];
  		// }else{
  		// 	$_POST['other'] = "D/{$depart[0]['Depart_Time']}";
  		// 	$_POST['end'] = $depart[0]['Arrive'];
  		// }
  		// 跑avh 
  		// continue;

  	}
  	echo json_encode($source_array);


  }

  // 计划任务
  public function run(){
  	import('vender/eterm/app.php');
  	$m_source = model('low_cabin_source');
  	$m_result = model('low_cabin_result');
  	$m_group  = model('low_cabin_group');
  	// 未跑数据
  	$result_source = $m_source -> where('Status = 0')->limit('3')->select();
  	if(!$result_source) return;
  	
  	$qt   = new \qt('dongmin', '12341234', 'BJS248');
 		$fsi_Result = array();


  	foreach ($result_source as $source) {
  		
  		$result_group  = $m_result->where("`Sid` = {$source['Id']}")->distinct("`Routing_Group`")->select();
			
  		// 每个航段均单独查询fsi
  		foreach ($result_group as $groupDate) {

	  		$group_list = $m_group->where("`Sid` = {$source['Id']} AND `GroupId` = {$groupDate['Routing_Group']} AND `Status`= 0")->order('`CabinInLevel` DESC')->select();

	  		// 查询不到 group
		  	if(!$group_list){
		  		$m_source->where("`Id` = {$source['Id']} ")->update(array('Status' => 1));
		  		continue;
		  	}

  			// 批量查询 fsi
  			foreach ($group_list as $key => $group_value) {
		  		$cur_fsi_result = array();
		  		$qt->command($group_value['Fsi']);
		  		preg_match_all('/\d\s(\w+\+?\*?)\s+(\d+)\sCNY/', $qt->rtTmp(), $priceArr);
		  		// var_dump($group_value);
		  		var_dump( $qt->rtTmp() );
		  		// var_dump($priceArr);
		  		// 存在价格，若有多个值取最小的
		  		if(isset($priceArr[2])){
		  			$min = $this->array_min($priceArr[2]);
		  		}
		  		array_push($fsi_Result, array(
		  			'Group'        => $groupDate['Routing_Group'],
		  			// 当去程和回程同舱位时，价格会相同
		  			// 'Result_Price' => $source['Price'],
		  			'Sid'          => $source['Id'],
		  			'Id'           => $group_value['Id'],
		  			'Price'        => isset($min)? $min['value'] : '',
		  			'FareBasis'    => isset($min)? $priceArr[1][$min['key']] : '',
		  			'Cabin'        => $group_value['Cabin']
		  		));
  			}

	  	}

  		ob_flush();
  		flush();
  	}

  	if(empty($fsi_Result)) return;

		// 价格最小的组
		$min_result_group    = array();
  	foreach ($fsi_Result as $key => $update_group) {
	  	// 更新m_group
	  	$m_group->where("`Id` = {$update_group['Id']}")->update(array('Total_Price'=>$update_group['Price'], 'FareBasis'=>$update_group['FareBasis'], 'Status'=>($update_group['Price'] == '' ? -1 : 1)));

  		if(empty($insert_result_group) || ($update_group['Price'] != '' && $min_result_group['Price'] >= $update_group['Price']))
	  		$min_result_group = $update_group;

	  	// 当组的序号发生变化时，更新m_result
	  	if(($min_result_group['Sid'] != $update_group['Sid'] || $min_result_group['Group'] != $update_group['Group']) || count($fsi_Result) -1 == $key){
	  		
	  		// 更新 m_result 和 m_resource 结果，当所有组查询无结果时。
	  		// 但是当为无中转时，group去程和返程均为0，则会导致去程与回程均更新，但是又去程更新回程未更新的情况。
	  		// 暂定为均认为去程和回程一同更新，因为m_group查询结果也是同时查询的，只要fsi生成无问题即可
				$m_result->where("`Sid` = {$min_result_group['Sid']} AND `Routing_Group` = {$min_result_group['Group']} ")->update(array(
					'LC_Status'    => 1, 
					'LC_Price'     => $min_result_group['Price'], 
					'LC_FareBasis' => $min_result_group['FareBasis'], 
					'LC_Cabin'     => $min_result_group['Cabin'],
				));
	  		$min_result_group    = array();
			}

	  }
  }




}