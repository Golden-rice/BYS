<?php
namespace admin\Controller;
use BYS\Controller;
class BasisController extends Controller {

	// 航空公司 basis_aircompany <- basis_country
    public function aircompany(){
        // 展示航班号、航空公司详细信息
        // 拆分出：出发、到达
        $this->display();
    }

    // 热门城市 数据来源 e_cmd_xfsd_result
    public function hotCity(){
        $this->display();
    }

    public function routing(){
        $this->display();
    }

    public function searchAircompany(){
        $aircompany = model('aircompany');

        $result = $aircompany -> select(); // 
        if(!empty($result[0])) {
            echo json_encode(array('status' => 'success', 'result'=>$result));
        }else{
            echo json_encode(array('status' => 'error', 'msg' => '出现错误'));
        }
    }

    public function findAircompany(){
        $aircompany = model('aircompany');
        $flight     = model('flight');
        $result     = $aircompany -> where("Air_Code = '{$_POST['airCompanyCode']}'") -> select(); // 
        $col        = $result[0];
        if($col['Air_Code'])
            $result_flight = $flight -> where("Fli_Airport = '{$col['Air_Code']}'") -> select(); // 
        else
            $result_flight = array();

        if(!empty($result_flight[0])) {
            $result_cnto = $this->searchCnTo($result[0]['Air_Code']);
            echo json_encode(array('status' => 'success', 'result'=>$col, 'result_flight'=>$result_flight, 'result_cnto'=>$result_cnto));
        }else{
            echo json_encode(array('status' => 'error', 'msg' => '出现错误'));
        }   
    }

    // 查询从中国始发
    public function searchCnTo($aircompany = ''){
        $cnto       = model('CnTo');
        $aircompany = $aircompany == ''? $_POST['aircompany'] : $aircompany;
        $result     = $cnto -> where("`CNTo_Aircompany` = '{$aircompany}' OR `CNTo_Aircompany` = '*{$aircompany}' ORDER BY `Id`") -> select();

        if($aircompany == ''){
            if(!empty($result[0])) 
                echo json_encode(array('status' => 'success', 'result'=>$result));
            else
                echo json_encode(array('status' => 'error', 'msg' => '出现错误'));
        }else{
            if(!empty($result[0])) {
                return $result;
            }else{
                return false;
            }
        }
    }

    // 查询舱位对照表
    public function searchCabinRule(){
        $cabin_rule  = model('cabin_rule'); 

        // 筛选舱位，可能多个
        if( isset($_POST['aircompany']) && $_POST['aircompany'] != ''){
            // 查看航空公司舱位等级
            $cabin_rule    = $cabin_rule->where("`Cab_AircompanyCode` = '{$_POST['aircompany']}' ")->select('`Cab_AircompanyCode` AS aircompany, `Cab_Description` AS description, `Cab_Code` AS cabin, `Cab_BasicCode` AS basisCode');
        }  

        echo json_encode(array(
            'cabin_rule' => isset($cabin_rule)? $cabin_rule : array(),
            'msg'        => \BYS\Report::printLog(),
        ));
    }


    public function searchRouting(){
        $flight            = model('flight');
        $toCity            = model('AirportCityCode');
        $aircompany        = $_POST['airCompany'];
        $dep               = $_POST['dep'];
        $arr               = $_POST['arr'];
        $result_out_first  = $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Dep = '{$dep}'") -> select(); // 出境第一段结果
        $result_out_second = $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Arr = '{$arr}'") -> select(); // 出境第二段结果
        $result_in_first   = $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Dep = '{$arr}'") -> select(); // 回境第一段到达结果
        $result_in_second  = $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Arr = '{$dep}'") -> select(); // 回境第二段到达结果

        // 转换城市代码
        $depCityResult = $toCity -> where("`ACC_Code` = '{$dep}'")->select();
        $arrCityResult = $toCity -> where("`ACC_Code` = '{$arr}'")->select();
        $depCity       = $depCityResult[0]['ACC_CityCode'];
        $arrCity       = $arrCityResult[0]['ACC_CityCode'];
        // 携程低价中的航路
        import('vender/api/OpenApi.class.php');
        $api = new \Api\OpenApi;
        $url = "https://Intlflightapi.ctrip.com/Intlflightapi/LowPrice.asmx?WSDL";
        $ctripOutboundTravelDate = date('Y-m-d', time()+24*60*60); // 明天 
        $ctripInboundTravelDate  = date('Y-m-d', time()+7*24*60*60); // 明天+ 7 
        $query = array(
            'TripType'=> 'RT',// OW: RT
            'DepartCity'=> $depCity,
            'ArriveCity'=> $arrCity,
            'Owner'=> $aircompany,
            'SeatGrade'=> 'Y', // F头等舱:C公务舱:W:超级经济舱:Y经济舱
            'OutboundTravelDate'=> $ctripOutboundTravelDate, 
            'InboundTravelDate'=> $ctripInboundTravelDate, 
            'ProductType'=> 'ALL', // PRIFARE : PUBFARE : ALL
            'PassengerNum'=> '1', 
            'PassengerEligibility'=>'ADT', // ADT普通: STU学生: LAB劳工: EMI移民: SEA海员
            'IsHasTax'=> 'False', // True: False
            'LowPriceSort'=> 'TripType' // Price 外放总价: TripType 行程
            );
        $xml = $api->requestXML(9, $query, $url);
        $result = simplexml_load_string($xml);
        if($result_out_first && $result_out_second) {
            echo json_encode(array(
            'status'        => 'success', 
            'outboundFirst' => $result_out_first, 
            'outboundSecond'=> $result_out_second,
            'inboundFirst'  => $result_in_first,
            'inboundSecond' => $result_in_second, 
            'lowprice'      => $result->LowPriceResponse->InfoList, // 
            'ctripTravelDate' => array(
                'outboundTravelDate' => $ctripOutboundTravelDate,
                'inboundTravelDate'  => $ctripInboundTravelDate,
            )
            )); 
        }else{
            echo json_encode(array('status' => 'error', 'msg' => '出现错误'));
        }   
        // fsi 检验
    }

    // 生成热门城市
    public function setHotCity(){
        if(!isset($_POST['selected'])) return;
        $hot_city_model     = model('hot_city');
        $price_source_model = model('price_source');

        // 清空表 
        $hot_city_model->deleteAll();
        $price_source_model->deleteAll();
        // $hot_city_model -> deleteAllAction();

        $hotCityArray   = json_decode($_POST['selected']['data'], true);
        $addAll         = array();
        foreach ($hotCityArray as $key => $value) {
            $addAll[]   = array(
                'HC_Depart'            => $value['depart'],
                'HC_Arrive'            => $value['arrive'],
                'HC_Aircompany'        => $value['aircompany'],
                'HC_Query'             => $_POST['selected']['query'],
                'HC_Cabin'             => $_POST['selected']['cabin'],
                'HC_XfsdResult_Status' => 0,
                'HC_AvhResult_Status'  => 0,
                'HC_Status'            => 0,
            );
        }
        $result = $hot_city_model->addAll($addAll);

    }

    // 查询热门城市
    public function searchHotCity(){
        if(!isset($_POST)) return;
        $hot_city    = model('hot_city');
        $xfsd_result = model('xfsd_result');

        // 查询所有热门城市
        $result      = $hot_city->select('`HC_Arrive` AS arrive, `HC_Depart` AS depart,`HC_Query` AS query, `HC_Cabin` AS cabin, `HC_Aircompany` AS aircompany ');

        if(!empty($result)){
            echo json_encode(array(
                'result'     => $result, 
                'msg'        => \BYS\Report::printLog()
            ));
        }
        elseif($result == false){
            echo json_encode(array('msg'=>'无数据'));
            // var_dump($xfsd_result->testSql(), $result);
        }
        else{
            echo json_encode(array('msg'=>\BYS\Report::printLog()));
        }
    }

    public function searchHotCitySelect(){
        $xfsd_result = model('xfsd_result');
        $cabin_rule  = model('cabin_rule'); 

        // 筛选舱位，可能多个
        if( isset($_POST['aircompany']) && $_POST['aircompany'] != ''){
            // 查看航空公司舱位等级
            $cabin_rule    = $cabin_rule->where("`Cab_AircompanyCode` = '{$_POST['aircompany']}' ")->select('`Cab_AircompanyCode` AS aircompany, `Cab_Description` AS description, `Cab_Code` AS cabin, `Cab_BasicCode` AS basisCode');
        }  

        // 所有出发城市
        $result_dep        = $xfsd_result->distinct('`xfsd_Dep` as depart')->select();
        // 所有舱位类型
        $result_cabin      = $xfsd_result->distinct('`xfsd_Cabin` as cabin')->select();
        // 所有rule类型
        $result_rule       = $xfsd_result->distinct('`xfsd_Rule` as rule')->select();
        // 所有区域类型
        $result_region     = $xfsd_result->distinct('`xfsd_Region` as region')->select();
        // 所有航空公司
        $result_aircompany = $xfsd_result->distinct('`xfsd_Owner` as aircompany')->select();

        echo json_encode(array(
            'depart'     => $result_dep,
            'cabin'      => $result_cabin, 
            'rule'       => $result_rule, 
            'region'     => $result_region,
            'aircompany' => $result_aircompany,
            'cabin_rule' => isset($cabin_rule)? $cabin_rule : array(),
            'msg'        => \BYS\Report::printLog(),
        ));

    }

    // 从result中查询热门城市
    public function searchHotCityFromResult(){
        if(!isset($_POST)) return;
        $xfsd_result = model('xfsd_result');
        $cabin_rule  = model('cabin_rule');

        // 查询条件
        $where    = "";
        $distinct = ' `xfsd_Arr` AS arrive, `xfsd_Dep` AS depart, `xfsd_Owner` AS aircompany';

        // 筛选出发地
        if( $_POST['depart'] != ''){
            $where    .= " `xfsd_Dep` = '{$_POST['depart']}' AND";
        }  

        // 筛选区域
        if( $_POST['region'] != ''){
            $where    .= " `xfsd_Region` = '{$_POST['region']}' AND";
        }     

        // 筛选舱位，可能多个
        if( $_POST['aircompany'] != ''){
            $where    .= " `xfsd_Owner` = '{$_POST['aircompany']}' AND";
            // 查看航空公司舱位等级
            $cabin_rule    = $cabin_rule->where("`Cab_AircompanyCode` = '{$_POST['aircompany']}' ")->select('`Cab_AircompanyCode` AS aircompany, `Cab_Description` AS description, `Cab_Code` AS cabin, `Cab_BasicCode` AS basisCode');
        }  

        // 筛选退票规则
        if( $_POST['rule'] != ''){
            $where    .= " `xfsd_Rule` = '{$_POST['rule']}' AND";
        }

        // 查看目的地
        $result            = $xfsd_result->where(rtrim($where, 'AND'))->distinct(rtrim($distinct, ','))->order('arrive')->select();

        if(!empty($result)){
            echo json_encode(array(
                'result'     => $result, 
                'msg'        => \BYS\Report::printLog(),
            ));
        }elseif($result == false){
            echo json_encode(array('msg'=>'无数据'));
            // var_dump($xfsd_result->testSql(), $result);
        }else{
            echo json_encode(array('msg'=>\BYS\Report::printLog()));
        }
    }

    // 机场城市代码 basis_airport_city_code <- basis_country | 城市表
    public function searchAirportAndCity(){
        $airportAndCity = '';
    }

    // 国家 basis_country
    public function country(){

    }

    // 舱位对照 basis_cabin_rule <- basis_airport_city_code
    public function cabin(){

    }

    // 航班 basis_flight
    public function flight(){

    }

    // 三方 basis_custom_code
    public function customCode(){

    }

}