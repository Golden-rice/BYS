<?php
namespace admin\Controller;
use BYS\Controller;
class BasisController extends Controller {

	// 航空公司 basis_aircompany <- basis_country
    public function aircompany(){
        // 以航程划分，含有那些舱位，航班号；含航空公司详细信息
        // 这些舱位有哪些farebasis即使用规则
        // 利用farabasis的使用规则：详细内容拆分出可用日期、价格、退改、作用点、限制航班号
        $this->display();
    }

    public function searchAircompany(){
        $aircompany = model('aircompany');

        $result = $aircompany -> select(); // 
        if($result) {
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
            echo json_encode(array('status' => 'success', 'result'=>$col, 'result_flight'=>$result_flight));
        }else{
            echo json_encode(array('status' => 'error', 'msg' => '出现错误'));
        }   
    }

    public function routing(){
        $this->display();
    }

    public function searchRouting(){
        $flight           = model('flight');
        $toCity           = model('AirportCityCode');
        $aircompany       = $_POST['airCompany'];
        $dep              = $_POST['dep'];
        $arr              = $_POST['arr'];
        $result_out_first = $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Dep = '{$dep}'") -> select(); // 出境第一段结果
        $result_out_second= $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Arr = '{$arr}'") -> select(); // 出境第二段结果
        $result_in_first  = $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Dep = '{$arr}'") -> select(); // 回境第一段到达结果
        $result_in_second = $flight -> where("Fli_Airport = '{$aircompany}' AND Fli_Arr = '{$dep}'") -> select(); // 回境第二段到达结果

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

    // 机场城市代码 basis_airport_city_code <- basis_country | 城市表
    public function airportAndCity(){

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

    // 从中国出发 basisi_cn_to
    public function CNto(){

    }
}