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
        if($result['Air_Code'])
            $result_flight = $flight -> where("Fli_Airport = '{$result['Air_Code']}'") -> select(); // 
        else
            $result_flight = array();

        if($result) {
            echo json_encode(array('status' => 'success', 'result'=>$result, 'result_flight'=>$result_flight));
        }else{
            echo json_encode(array('status' => 'error', 'msg' => '出现错误'));
        }   
    }

    public function routing(){
        $this->display();
    }

    public function searchRouting(){
        $flight     = model('flight');
        $result_dep = $flight -> where("Fli_Airport = '{$_POST['airCompany']}' AND Fli_Dep = '{$_POST['dep']}'") -> select(); // 出发结果
        $result_arr = $flight -> where("Fli_Airport = '{$_POST['airCompany']}' AND Fli_Arr = '{$_POST['arr']}'") -> select(); // 到达结果

        if($result_dep && $result_arr) {
            echo json_encode(array('status' => 'success', 'result_dep'=>$result_dep, 'result_arr'=>$result_arr));
        }else{
            echo json_encode(array('status' => 'error', 'msg' => '出现错误'));
        }   

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